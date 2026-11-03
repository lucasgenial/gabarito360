<?php

namespace App\Services;

use App\Support\Catalogo\ConferenciaImportacaoCatalogo;
use App\Support\Catalogo\EntradaCatalogo;
use App\Support\Catalogo\PoliticaEdicaoCatalogo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Publicação lógica do catálogo público. Não publica a aplicação nem altera escolas. */
class PublicarCatalogoInstituicoes
{
    public function __construct(private ConferenciaImportacaoCatalogo $conferencia) {}

    public function executar(string $importacaoId, string $arquivoUfs, int $lote = 1000, ?int $maxLotes = null,
        ?string $substituir = null, ?string $motivo = null, bool $simular = false): array
    {
        if ($lote<1 || $lote>1000 || ($maxLotes !== null && $maxLotes<1)) throw new RuntimeException('Limites de lote inválidos.');
        $dados = $this->conferencia->conferir($importacaoId,$arquivoUfs); $i = $dados['importacao'];
        if ($simular) {
            $existente = DB::table('catalogo_publicacoes')->where('importacao_id',$i->id)->first();
            if (!$existente || $existente->estado !== 'publicada') {
                $vigenteId = DB::table('catalogo_fontes')->where('nome',$i->fonte)->value('publicacao_id');
                $vigente = $vigenteId ? DB::table('catalogo_publicacoes')->where('id',$vigenteId)->first() : null;
                PoliticaEdicaoCatalogo::conferir((int)$i->ano,$vigente,$substituir,$motivo);
            }
            return ['modo'=>'simulacao_sem_publicacao','importacao_id'=>$i->id,'total'=>(int)$i->total_esperado];
        }
        $token = (string)Str::uuid();
        $p = DB::transaction(function () use ($i, $dados, $substituir, $motivo, $token) {
            $fonte = DB::table('catalogo_fontes')->where('nome',$i->fonte)->lockForUpdate()->first();
            if (!$fonte) throw new RuntimeException('Fonte de publicação não configurada.');
            $vigente = $fonte->publicacao_id ? DB::table('catalogo_publicacoes')->where('id',$fonte->publicacao_id)->first() : null;
            $p = DB::table('catalogo_publicacoes')->where('importacao_id',$i->id)->lockForUpdate()->first();
            if ($p && $p->estado === 'publicada') return $p;
            if ($p && $p->lease_token && $p->lease_ate && Carbon::parse($p->lease_ate)->isFuture()) throw new RuntimeException('Publicação já está em execução.');
            PoliticaEdicaoCatalogo::conferir((int)$i->ano,$vigente,$substituir,$motivo);
            if ($p && $p->anterior_id !== $fonte->publicacao_id && $substituir !== $fonte->publicacao_id) throw new RuntimeException('Ponteiro vigente mudou; revisar substituição.');
            $this->prepararGeografia($i->geografia_id,$dados['geografia'],$dados['nomes_ufs']);
            $agora = now();
            if (!$p) {
                $id = (string)Str::uuid();
                DB::table('catalogo_publicacoes')->insert(['id'=>$id,'importacao_id'=>$i->id,'geografia_id'=>$i->geografia_id,
                    'fonte'=>$i->fonte,'ano'=>$i->ano,'estado'=>'preparando','total'=>$i->total_esperado,'cursor'=>0,'created_at'=>$agora,'updated_at'=>$agora]);
            } else $id = $p->id;
            DB::table('catalogo_publicacoes')->where('id',$id)->update(['estado'=>'preparando','anterior_id'=>$fonte->publicacao_id,
                'motivo'=>$motivo,'lease_token'=>$token,'lease_ate'=>now()->addMinutes(30),'updated_at'=>now()]);
            return DB::table('catalogo_publicacoes')->where('id',$id)->first();
        });
        if ($p->estado === 'publicada') return $this->recibo($p->id);
        try {
            $lotes = 0;
            foreach (DB::table('catalogo_observacoes')->where('importacao_id',$i->id)->where('ordem','>',$p->cursor)->lazyById($lote,'ordem')->chunk($lote) as $observacoes) {
                DB::transaction(function () use ($p, $token, $observacoes) {
                    $atual = $this->bloquear($p->id,$token);
                    if ((int)$observacoes->first()->ordem !== (int)$atual->cursor+1) throw new RuntimeException('Cursor da projeção divergente.');
                    $instituicoes = []; $identificadores = []; $unidades = []; $entradas = [];
                    foreach ($observacoes as $o) {
                        $e = EntradaCatalogo::projetar($o,$p);
                        $instituicoes[] = ['id'=>$e['instituicao_id'],'created_at'=>now()];
                        $identificadores[] = ['namespace'=>$e['namespace'],'codigo'=>$e['codigo'],'instituicao_id'=>$e['instituicao_id']];
                        $unidades[] = ['id'=>$e['unidade_id'],'instituicao_id'=>$e['instituicao_id'],'chave_unidade'=>'principal','natureza'=>$e['natureza']];
                        $entradas[] = $e + ['projecao_sha256'=>hash('sha256',EntradaCatalogo::serializar($e))];
                    }
                    DB::table('catalogo_instituicoes')->insertOrIgnore($instituicoes);
                    DB::table('catalogo_identificadores')->insertOrIgnore($identificadores);
                    DB::table('catalogo_unidades')->insertOrIgnore($unidades);
                    DB::table('catalogo_entradas')->insert($entradas);
                    DB::table('catalogo_publicacoes')->where('id',$p->id)->update(['cursor'=>(int)$observacoes->last()->ordem,'lease_ate'=>now()->addMinutes(30),'updated_at'=>now()]);
                });
                $lotes++;
                if ($maxLotes !== null && $lotes >= $maxLotes) {
                    DB::transaction(function () use ($p, $token) { $this->bloquear($p->id,$token); $this->liberar($p->id); });
                    return $this->recibo($p->id);
                }
            }
            $hash = $this->conferirProjecao($p);
            DB::transaction(function () use ($p, $token, $hash) {
                $fonte = DB::table('catalogo_fontes')->where('nome',$p->fonte)->lockForUpdate()->first();
                $atual = $this->bloquear($p->id,$token);
                if ($fonte->publicacao_id !== $atual->anterior_id || (int)$atual->cursor !== (int)$atual->total) throw new RuntimeException('Ponteiro ou cursor mudou antes da publicação.');
                DB::table('catalogo_publicacoes')->where('id',$p->id)->update(['estado'=>'publicada','projecao_sha256'=>$hash,'publicado_em'=>now(),'lease_token'=>null,'lease_ate'=>null,'updated_at'=>now()]);
                DB::table('catalogo_fontes')->where('nome',$p->fonte)->update(['publicacao_id'=>$p->id]);
                DB::table('catalogo_publicacao_eventos')->insert(['publicacao_id'=>$p->id,'anterior_id'=>$atual->anterior_id,'acao'=>'publicada','motivo'=>$atual->motivo,'projecao_sha256'=>$hash,'ocorrido_em'=>now()]);
            });
            return $this->recibo($p->id);
        } catch (\Throwable $e) {
            DB::table('catalogo_publicacoes')->where('id',$p->id)->where('lease_token',$token)->update(['estado'=>'falhou','lease_token'=>null,'lease_ate'=>null,'updated_at'=>now()]);
            throw $e;
        }
    }

    private function prepararGeografia(string $id, array $geo, array $nomes): void
    {
        $ufs = [];
        foreach ($geo['ufs'] as $codigo=>$sigla) $ufs[] = ['geografia_id'=>$id,'codigo_ibge'=>$codigo,'sigla'=>$sigla,'nome'=>$nomes[$codigo]];
        DB::table('catalogo_ufs')->insertOrIgnore($ufs);
        $municipios = [];
        foreach ($geo['municipios'] as $codigo=>$m) $municipios[] = ['geografia_id'=>$id,'codigo_ibge'=>(string)$codigo,'uf'=>$m['uf'],'nome'=>$m['nome'],'nome_normalizado'=>EntradaCatalogo::normalizar($m['nome'])];
        foreach (array_chunk($municipios,1000) as $lote) DB::table('catalogo_municipios')->insertOrIgnore($lote);
        if (DB::table('catalogo_ufs')->where('geografia_id',$id)->count()!==27 || DB::table('catalogo_municipios')->where('geografia_id',$id)->count()!==count($municipios)) throw new RuntimeException('Geografia projetada incompleta.');
        foreach (DB::table('catalogo_ufs')->where('geografia_id',$id)->get() as $u) if ($u->nome !== $nomes[$u->codigo_ibge] || $u->sigla !== $geo['ufs'][$u->codigo_ibge]) throw new RuntimeException('UF projetada divergente.');
        foreach (DB::table('catalogo_municipios')->where('geografia_id',$id)->get() as $m) {
            $esperado = $geo['municipios'][$m->codigo_ibge] ?? null;
            if (!$esperado || $m->uf !== $esperado['uf'] || $m->nome !== $esperado['nome'] || $m->nome_normalizado !== EntradaCatalogo::normalizar($esperado['nome'])) throw new RuntimeException('Município projetado divergente.');
        }
    }

    private function conferirProjecao(object $p): string
    {
        $digest = hash_init('sha256'); $ordem = 0;
        foreach (DB::table('catalogo_entradas as e')->join('catalogo_observacoes as o','o.id','=','e.observacao_id')->where('e.publicacao_id',$p->id)
            ->select('e.*','o.payload as fonte_payload','o.ordem as fonte_ordem')->lazyById(1000,'e.ordem','ordem') as $e) {
            if ((int)$e->ordem !== ++$ordem) throw new RuntimeException('Projeção fora de sequência.');
            $esperado = EntradaCatalogo::projetar((object)['id'=>$e->observacao_id,'ordem'=>$e->fonte_ordem,'payload'=>$e->fonte_payload],$p);
            foreach ($esperado as $campo=>$valor) {
                $atual = is_int($valor) ? (int)$e->$campo : $e->$campo;
                if ($atual !== $valor) throw new RuntimeException('Projeção incompatível com observação.');
            }
            $json = EntradaCatalogo::serializar($esperado);
            if (!hash_equals($e->projecao_sha256,hash('sha256',$json))) throw new RuntimeException('Digest da projeção divergente.');
            hash_update($digest,$json."\n");
        }
        if ($ordem !== (int)$p->total) throw new RuntimeException('Projeção incompleta.');
        return hash_final($digest);
    }

    private function bloquear(string $id, string $token): object
    {
        $p = DB::table('catalogo_publicacoes')->where('id',$id)->lockForUpdate()->first();
        if (!$p || $p->estado !== 'preparando' || $p->lease_token !== $token || !$p->lease_ate || !Carbon::parse($p->lease_ate)->isFuture()) throw new RuntimeException('Lease da publicação não está vigente.');
        return $p;
    }

    private function liberar(string $id): void { DB::table('catalogo_publicacoes')->where('id',$id)->update(['lease_token'=>null,'lease_ate'=>null,'updated_at'=>now()]); }

    private function recibo(string $id): array
    {
        $p = DB::table('catalogo_publicacoes')->where('id',$id)->first();
        return ['publicacao_id'=>$id,'estado'=>$p->estado,'cursor'=>(int)$p->cursor,'total'=>(int)$p->total,
            'vigente'=>DB::table('catalogo_fontes')->where('nome',$p->fonte)->value('publicacao_id') === $id];
    }
}
