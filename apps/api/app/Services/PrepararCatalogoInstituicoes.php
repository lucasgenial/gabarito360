<?php

namespace App\Services;

use App\Support\Catalogo\LeitorInstituicoes;
use App\Support\Catalogo\ManifestoCatalogo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Staging público. Nunca escreve em escolas, censo legado ou tabelas acadêmicas. */
class PrepararCatalogoInstituicoes
{
    public function __construct(private ManifestoCatalogo $validador) {}

    public function executar(string $arquivo, string $fonte, int $ano, string $ufs, string $municipios,
        string $sha256, string $encoding = 'Windows-1252', int $tamanhoLote = 1000, ?int $maxLotes = null, bool $simular = false): array
    {
        if ($tamanhoLote < 1 || $tamanhoLote > 1000 || ($maxLotes !== null && $maxLotes < 1)) throw new RuntimeException('Limites de lote inválidos.');
        $validacao = $this->validador->validar($arquivo, $fonte, $ano, $ufs, $municipios, $encoding, $sha256, true);
        $m = $validacao['manifesto']; $geo = $validacao['geografia'];
        if ($simular) return $m;
        $geoId = hash('sha256', $m['hashes']['ufs'].$m['hashes']['municipios']);
        $snapshot = json_encode($geo, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $chave = hash('sha256', $fonte.':'.$ano.':'.$m['schema'].':'.$encoding.':'.$m['sha256'].':'.$geoId);
        $id = DB::transaction(function () use ($geoId, $snapshot, $chave, $m) {
            DB::table('catalogo_geografias')->insertOrIgnore(['id'=>$geoId, 'ufs_sha256'=>$m['hashes']['ufs'],
                'municipios_sha256'=>$m['hashes']['municipios'], 'snapshot'=>$snapshot, 'snapshot_sha256'=>hash('sha256',$snapshot), 'created_at'=>now()]);
            $g = DB::table('catalogo_geografias')->where('id',$geoId)->first();
            if (!$g || !hash_equals($g->snapshot_sha256, hash('sha256',$snapshot)) || $g->snapshot !== $snapshot) throw new RuntimeException('Snapshot geográfico divergente.');
            DB::table('catalogo_importacoes')->insertOrIgnore(['id'=>(string)Str::uuid(), 'chave_ingestao'=>$chave,
                'geografia_id'=>$geoId, 'fonte'=>$m['fonte'], 'ano'=>$m['ano'], 'schema_versao'=>$m['schema'],
                'arquivo_sha256'=>$m['sha256'], 'manifesto'=>json_encode($m, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
                'estado'=>'validando', 'total_esperado'=>$m['registros'], 'cursor'=>0, 'created_at'=>now(), 'updated_at'=>now()]);
            return DB::table('catalogo_importacoes')->where('chave_ingestao',$chave)->value('id');
        });
        $token = (string)Str::uuid();
        $cursor = DB::transaction(function () use ($id, $token) {
            $i = DB::table('catalogo_importacoes')->where('id',$id)->lockForUpdate()->first();
            if ($i->estado === 'validado') return null;
            if (!in_array($i->estado, ['validando','falhou'], true)) throw new RuntimeException('Estado não aceita preparação.');
            if ($i->lease_token && $i->lease_ate && Carbon::parse($i->lease_ate)->isFuture()) throw new RuntimeException('Importação já está em execução.');
            DB::table('catalogo_importacoes')->where('id',$id)->update(['estado'=>'validando', 'lease_token'=>$token, 'lease_ate'=>now()->addMinutes(30), 'updated_at'=>now()]);
            return (int)$i->cursor;
        });
        if ($cursor === null) return $this->recibo($id);
        try {
            $lote = []; $ordem = 0; $lotes = 0;
            foreach ((new LeitorInstituicoes($geo['municipios']))->ler($arquivo, $fonte, $ano, $encoding) as $r) {
                $ordem++;
                if ($ordem <= $cursor) continue;
                $payload = ManifestoCatalogo::payload($r);
                $lote[] = ['importacao_id'=>$id, 'ordem'=>$ordem, 'chave_fonte'=>$r['namespace'].':'.$r['codigo'],
                    'payload'=>$payload, 'payload_sha256'=>hash('sha256',$payload), 'created_at'=>now()];
                if (count($lote) === $tamanhoLote) {
                    $this->gravarLote($id, $token, $cursor, $lote); $cursor = $ordem; $lote = []; $lotes++;
                    if ($maxLotes !== null && $lotes >= $maxLotes) return $this->pausar($id, $token);
                }
            }
            if ($lote) { $this->gravarLote($id, $token, $cursor, $lote); $cursor = $ordem; }
            $this->validador->conferirIntegridade(['fonte'=>$arquivo,'ufs'=>$ufs,'municipios'=>$municipios], $m['hashes']);
            $this->conferirObservacoes($id, $m);
            DB::transaction(function () use ($id, $token, $m, $cursor) {
                $i = $this->bloquear($id, $token);
                if ((int)$i->cursor !== $cursor || $cursor !== $m['registros']) throw new RuntimeException('Cursor final divergente.');
                DB::table('catalogo_importacoes')->where('id',$id)->update(['estado'=>'validado', 'validado_em'=>now(), 'lease_token'=>null, 'lease_ate'=>null, 'updated_at'=>now()]);
            });
            return $this->recibo($id);
        } catch (\Throwable $e) {
            DB::table('catalogo_importacoes')->where('id',$id)->where('lease_token',$token)
                ->update(['estado'=>'falhou', 'lease_token'=>null, 'lease_ate'=>null, 'updated_at'=>now()]);
            throw $e;
        }
    }

    private function bloquear(string $id, string $token): object
    {
        $i = DB::table('catalogo_importacoes')->where('id',$id)->lockForUpdate()->first();
        if (!$i || $i->estado !== 'validando' || $i->lease_token !== $token || !$i->lease_ate || !Carbon::parse($i->lease_ate)->isFuture()) {
            throw new RuntimeException('Lease da importação não está vigente.');
        }
        return $i;
    }

    private function gravarLote(string $id, string $token, int $cursor, array $lote): void
    {
        DB::transaction(function () use ($id, $token, $cursor, $lote) {
            $i = $this->bloquear($id, $token);
            if ((int)$i->cursor !== $cursor || $lote[0]['ordem'] !== $cursor + 1) throw new RuntimeException('Cursor do lote divergente.');
            DB::table('catalogo_observacoes')->insert($lote);
            DB::table('catalogo_importacoes')->where('id',$id)->update(['cursor'=>end($lote)['ordem'], 'lease_ate'=>now()->addMinutes(30), 'updated_at'=>now()]);
        });
    }

    private function pausar(string $id, string $token): array
    {
        DB::transaction(function () use ($id, $token) {
            $this->bloquear($id, $token);
            DB::table('catalogo_importacoes')->where('id',$id)->update(['lease_token'=>null,'lease_ate'=>null,'updated_at'=>now()]);
        });
        return $this->recibo($id);
    }

    private function conferirObservacoes(string $id, array $manifesto): void
    {
        $digest = hash_init('sha256'); $ordem = 0;
        foreach (DB::table('catalogo_observacoes')->where('importacao_id',$id)->lazyById(1000, 'ordem') as $o) {
            if ((int)$o->ordem !== ++$ordem || !hash_equals($o->payload_sha256,hash('sha256',$o->payload))) throw new RuntimeException('Observações incompletas ou adulteradas.');
            hash_update($digest, $o->payload."\n");
        }
        if ($ordem !== $manifesto['registros'] || !hash_equals($manifesto['observacoes_sha256'],hash_final($digest))) throw new RuntimeException('Manifesto das observações divergente.');
    }

    private function recibo(string $id): array
    {
        $i = DB::table('catalogo_importacoes')->where('id',$id)->first();
        return ['modo'=>'staging_sem_publicacao','importacao_id'=>$id,'estado'=>$i->estado,'cursor'=>(int)$i->cursor,'total'=>(int)$i->total_esperado];
    }
}
