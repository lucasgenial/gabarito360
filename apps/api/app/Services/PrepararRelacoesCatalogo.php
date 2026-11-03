<?php

namespace App\Services;

use App\Support\Catalogo\LeitorRelacoesCatalogo;
use App\Support\Catalogo\PacoteRelacoesCatalogo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Relações públicas entre referências: não cria campi, escolas ou vínculos de pessoas. */
class PrepararRelacoesCatalogo
{
    public function __construct(private LeitorRelacoesCatalogo $leitor) {}

    public function executar(string $arquivo, string $basicaId, string $superiorId, string $sha256,
        int $lote = 1000, ?int $maxLotes = null, bool $simular = false): array
    {
        if ($lote<1 || $lote>1000 || ($maxLotes !== null && $maxLotes<1)) throw new RuntimeException('Limites de lote inválidos.');
        $sha256 = strtolower($sha256);
        $basica = $this->conferirPublicacao($basicaId,'basica');
        $superior = $this->conferirPublicacao($superiorId,'superior');
        if ($sha256 !== $basica->arquivo_sha256) throw new RuntimeException('Arquivo não pertence à edição básica informada.');
        $pacote = $this->leitor->preparar($arquivo,$sha256,(int)$basica->total,(int)$basica->ano);
        $m = $pacote->manifesto;
        $chave = hash('sha256',$basicaId.':'.$superiorId.':'.LeitorRelacoesCatalogo::SCHEMA.':'.$sha256);
        if ($simular) {
            $existente = DB::table('catalogo_relacao_conjuntos')->where('chave',$chave)->first();
            if (!$existente || $existente->estado !== 'publicado') $this->conferirFontes(DB::table('catalogo_fontes')->pluck('publicacao_id','nome')->all(),$basicaId,$superiorId);
            return ['modo'=>'simulacao_sem_gravacao','basica_id'=>$basicaId,'superior_id'=>$superiorId,'manifesto'=>$m];
        }
        $token = (string)Str::uuid();
        $c = DB::transaction(function () use ($basicaId, $superiorId, $chave, $m, $token) {
            $fontes = $this->fontesBloqueadas();
            $v = DB::table('catalogo_relacao_vigencia')->where('nome','nacional')->lockForUpdate()->first();
            $c = DB::table('catalogo_relacao_conjuntos')->where('chave',$chave)->lockForUpdate()->first();
            if ($c && $c->estado === 'publicado') return $c;
            if ($c && !in_array($c->estado,['preparando','falhou'],true)) throw new RuntimeException('Estado de relações não aceita retomada.');
            $this->conferirFontes($fontes,$basicaId,$superiorId);
            if ($c && $c->lease_token && $c->lease_ate && Carbon::parse($c->lease_ate)->isFuture()) throw new RuntimeException('Relações já estão em execução.');
            if (!$c) {
                $id = (string)Str::uuid();
                DB::table('catalogo_relacao_conjuntos')->insert(['id'=>$id,'chave'=>$chave,'basica_id'=>$basicaId,'superior_id'=>$superiorId,
                    'arquivo_sha256'=>$m['sha256'],'schema_versao'=>$m['schema'],'manifesto'=>PacoteRelacoesCatalogo::json($m),
                    'estado'=>'preparando','total'=>$m['total'],'cursor'=>0,'created_at'=>now(),'updated_at'=>now()]);
            } else {
                $id = $c->id;
                if (json_decode($c->manifesto,true,512,JSON_THROW_ON_ERROR) !== $m) throw new RuntimeException('Manifesto da retomada de relações divergente.');
            }
            DB::table('catalogo_relacao_conjuntos')->where('id',$id)->update(['estado'=>'preparando','anterior_id'=>$v->conjunto_id,
                'lease_token'=>$token,'lease_ate'=>now()->addMinutes(30),'updated_at'=>now()]);
            return DB::table('catalogo_relacao_conjuntos')->find($id);
        });
        if ($c->estado === 'publicado') return $this->recibo($c->id);
        try {
            $buffer = []; $lotes = 0;
            foreach ($pacote->linhas((int)$c->cursor) as $r) {
                $buffer[] = $r;
                if (count($buffer) === $lote) {
                    $this->gravarLote($c,$token,$buffer); $buffer = []; $lotes++;
                    if ($maxLotes !== null && $lotes >= $maxLotes) {
                        DB::transaction(function () use ($c,$token) { $this->bloquear($c->id,$token); $this->liberar($c->id); });
                        return $this->recibo($c->id);
                    }
                }
            }
            if ($buffer) $this->gravarLote($c,$token,$buffer);
            [$hash,$pendencias] = $this->conferirProjecao($c,$pacote);
            $this->leitor->conferirArquivo($arquivo,$sha256);
            DB::transaction(function () use ($c,$token,$hash,$pendencias) {
                $fontes = $this->fontesBloqueadas();
                $v = DB::table('catalogo_relacao_vigencia')->where('nome','nacional')->lockForUpdate()->first();
                $atual = $this->bloquear($c->id,$token);
                $this->conferirFontes($fontes,$c->basica_id,$c->superior_id);
                if ($v->conjunto_id !== $atual->anterior_id || (int)$atual->cursor !== (int)$atual->total) throw new RuntimeException('Vigência ou cursor de relações mudou antes do fechamento.');
                DB::table('catalogo_relacao_eventos')->insert(['conjunto_id'=>$c->id,'anterior_id'=>$atual->anterior_id,
                    'projecao_sha256'=>$hash,'total'=>$atual->total,'pendencias'=>$pendencias,'ocorrido_em'=>now()]);
                DB::table('catalogo_relacao_conjuntos')->where('id',$c->id)->update(['estado'=>'publicado','projecao_sha256'=>$hash,'pendencias'=>$pendencias,
                    'publicado_em'=>now(),'lease_token'=>null,'lease_ate'=>null,'updated_at'=>now()]);
                DB::table('catalogo_relacao_vigencia')->where('nome','nacional')->update(['conjunto_id'=>$c->id]);
            });
            return $this->recibo($c->id);
        } catch (\Throwable $e) {
            DB::table('catalogo_relacao_conjuntos')->where('id',$c->id)->where('lease_token',$token)
                ->update(['estado'=>'falhou','lease_token'=>null,'lease_ate'=>null,'updated_at'=>now()]);
            throw $e;
        }
    }

    private function conferirPublicacao(string $id, string $fonte): object
    {
        $p = DB::table('catalogo_publicacoes')->find($id);
        $i = $p ? DB::table('catalogo_importacoes')->find($p->importacao_id) : null;
        if (!$p || !$i || $p->fonte !== $fonte || $i->fonte !== $fonte || $p->estado !== 'publicada' || !$p->publicado_em
            || !$p->projecao_sha256 || $i->estado !== 'validado' || $i->geografia_id !== $p->geografia_id
            || (int)$i->ano !== (int)$p->ano || (int)$p->cursor !== (int)$p->total || (int)$p->total !== (int)$i->total_esperado
            || (int)$i->cursor !== (int)$i->total_esperado) throw new RuntimeException('Publicação de referência não está finalizada ou é incompatível.');
        $m = json_decode($i->manifesto,true,512,JSON_THROW_ON_ERROR);
        if (($m['sha256']??null) !== $i->arquivo_sha256 || ($m['hashes']['fonte']??null) !== $i->arquivo_sha256
            || DB::table('catalogo_entradas')->where('publicacao_id',$id)->count() !== (int)$p->total) throw new RuntimeException('Integridade da publicação de referência divergente.');
        $p->arquivo_sha256 = $i->arquivo_sha256;
        return $p;
    }

    private function fontesBloqueadas(): array
    {
        return DB::table('catalogo_fontes')->whereIn('nome',['basica','superior'])->orderBy('nome')->lockForUpdate()->pluck('publicacao_id','nome')->all();
    }

    private function conferirFontes(array $fontes, string $basica, string $superior): void
    {
        if (($fontes['basica']??null) !== $basica || ($fontes['superior']??null) !== $superior) throw new RuntimeException('Edições de referência deixaram de ser vigentes.');
    }

    private function mapa(string $publicacao, string $namespace, array $codigos): array
    {
        $mapa = [];
        foreach (array_chunk(array_values(array_unique($codigos)),500) as $lote) {
            foreach (DB::table('catalogo_entradas')->where('publicacao_id',$publicacao)->where('namespace',$namespace)->whereIn('codigo',$lote)
                ->get(['id','codigo','uf']) as $e) $mapa[$e->codigo] = $e;
        }
        return $mapa;
    }

    private function projetarLote(object $c, array $linhas): array
    {
        $codigosBase = array_column($linhas,'origem_codigo'); $codigosIes = [];
        foreach ($linhas as $r) {
            if ($r['tipo'] === 'sede_escolar') $codigosBase[] = $r['alvo_codigo']; else $codigosIes[] = $r['alvo_codigo'];
        }
        $base = $this->mapa($c->basica_id,'inep_escola',$codigosBase); $ies = $this->mapa($c->superior_id,'inep_ies',$codigosIes); $projecoes = [];
        foreach ($linhas as $r) {
            $origem = $base[$r['origem_codigo']]??null;
            if (!$origem) throw new RuntimeException('Origem da relação não pertence à publicação básica.');
            $alvo = $r['tipo'] === 'sede_escolar' ? ($base[$r['alvo_codigo']]??null) : ($ies[$r['alvo_codigo']]??null);
            $projecoes[] = ['conjunto_id'=>$c->id,'ordem'=>$r['ordem'],'linha_fonte'=>$r['linha_fonte'],'origem_codigo'=>$r['origem_codigo'],
                'origem_entrada_id'=>(int)$origem->id,'origem_publicacao_id'=>$c->basica_id,'tipo'=>$r['tipo'],
                'alvo_namespace'=>$r['alvo_namespace'],'alvo_codigo_bruto'=>$r['alvo_codigo_bruto'],'alvo_codigo'=>$r['alvo_codigo'],
                'alvo_entrada_id'=>$alvo ? (int)$alvo->id : null,'alvo_publicacao_id'=>$r['tipo'] === 'sede_escolar' ? $c->basica_id : $c->superior_id,
                'estado'=>$r['alerta']??($alvo ? 'referenciada' : 'alvo_ausente'),'uf_diferente'=>$alvo ? $alvo->uf !== $origem->uf : null];
        }
        return $projecoes;
    }

    private function gravarLote(object $c, string $token, array $linhas): void
    {
        $projecoes = $this->projetarLote($c,$linhas);
        DB::transaction(function () use ($c,$token,$linhas,$projecoes) {
            $atual = $this->bloquear($c->id,$token);
            if ((int)$atual->cursor+1 !== $linhas[0]['ordem']) throw new RuntimeException('Cursor do lote de relações divergente.');
            DB::table('catalogo_relacoes')->insert(array_map(fn($r)=>$r+['projecao_sha256'=>hash('sha256',PacoteRelacoesCatalogo::json($r))],$projecoes));
            $ids = DB::table('catalogo_relacoes')->where('conjunto_id',$c->id)->whereBetween('ordem',[$linhas[0]['ordem'],end($linhas)['ordem']])->pluck('id','ordem');
            $lacunas = [];
            foreach ($projecoes as $r) if ($r['estado'] !== 'referenciada') $lacunas[] = ['relacao_id'=>$ids[$r['ordem']],'motivo'=>$r['estado'],'registrada_em'=>now()];
            if ($lacunas) DB::table('catalogo_relacao_lacunas')->insert($lacunas);
            DB::table('catalogo_relacao_conjuntos')->where('id',$c->id)->update(['cursor'=>end($linhas)['ordem'],'lease_ate'=>now()->addMinutes(30),'updated_at'=>now()]);
        });
    }

    private function conferirProjecao(object $c, PacoteRelacoesCatalogo $pacote): array
    {
        $digest = hash_init('sha256'); $ordem = 0; $pendencias = 0; $buffer = [];
        foreach ($pacote->linhas() as $r) {
            // Pequenos blocos limitam memória sem uma consulta por relação.
            $buffer[] = $r;
            if (count($buffer)===1000) { $this->conferirBloco($c,$buffer,$digest,$ordem,$pendencias); $buffer = []; }
        }
        if (!empty($buffer)) $this->conferirBloco($c,$buffer,$digest,$ordem,$pendencias);
        if ($ordem !== (int)$c->total || DB::table('catalogo_relacoes')->where('conjunto_id',$c->id)->count() !== $ordem) throw new RuntimeException('Projeção de relações incompleta.');
        return [hash_final($digest),$pendencias];
    }

    private function conferirBloco(object $c, array $buffer, $digest, int &$ordem, int &$pendencias): void
    {
        $projecoes = $this->projetarLote($c,$buffer);
        $rows = DB::table('catalogo_relacoes')->where('conjunto_id',$c->id)->whereBetween('ordem',[$buffer[0]['ordem'],end($buffer)['ordem']])->get()->keyBy('ordem');
        $lacunas = DB::table('catalogo_relacao_lacunas')->whereIn('relacao_id',$rows->pluck('id')->all())->pluck('motivo','relacao_id');
        foreach ($projecoes as $esperado) {
            $atual = $rows[$esperado['ordem']]??null;
            if (!$atual || (int)$atual->ordem !== ++$ordem) throw new RuntimeException('Sequência de relações divergente.');
            foreach ($esperado as $campo=>$valor) {
                $lido = $atual->$campo;
                if ($lido !== null) { if (is_bool($valor)) $lido = (bool)$lido; elseif (is_int($valor)) $lido = (int)$lido; }
                if ($lido !== $valor) throw new RuntimeException('Relação projetada incompatível com a fonte.');
            }
            $json = PacoteRelacoesCatalogo::json($esperado);
            if (!hash_equals($atual->projecao_sha256,hash('sha256',$json))) throw new RuntimeException('Hash de relação divergente.');
            $motivo = $lacunas[$atual->id]??null;
            if (($esperado['estado'] === 'referenciada' && $motivo !== null) || ($esperado['estado'] !== 'referenciada' && $motivo !== $esperado['estado'])) throw new RuntimeException('Fila de lacunas incompatível.');
            if ($motivo !== null) $pendencias++;
            hash_update($digest,$json."\n");
        }
    }

    private function bloquear(string $id, string $token): object
    {
        $c = DB::table('catalogo_relacao_conjuntos')->where('id',$id)->lockForUpdate()->first();
        if (!$c || $c->estado !== 'preparando' || $c->lease_token !== $token || !$c->lease_ate || !Carbon::parse($c->lease_ate)->isFuture()) throw new RuntimeException('Lease de relações não está vigente.');
        return $c;
    }

    private function liberar(string $id): void { DB::table('catalogo_relacao_conjuntos')->where('id',$id)->update(['lease_token'=>null,'lease_ate'=>null,'updated_at'=>now()]); }

    private function recibo(string $id): array
    {
        $c = DB::table('catalogo_relacao_conjuntos')->find($id);
        return ['conjunto_id'=>$id,'estado'=>$c->estado,'cursor'=>(int)$c->cursor,'total'=>(int)$c->total,'pendencias'=>$c->estado === 'publicado' ? (int)$c->pendencias : null,
            'vigente'=>$c->estado === 'publicado' && DB::table('catalogo_relacao_vigencia')->where('nome','nacional')->where('conjunto_id',$id)
                ->whereExists(fn($q)=>$q->selectRaw('1')->from('catalogo_fontes')->where('nome','basica')->where('publicacao_id',$c->basica_id))
                ->whereExists(fn($q)=>$q->selectRaw('1')->from('catalogo_fontes')->where('nome','superior')->where('publicacao_id',$c->superior_id))->exists()];
    }
}
