<?php

namespace App\Services;

use App\Models\Usuario;
use App\Support\EstadoExportacaoTitular as Estado;
use Illuminate\Support\{Carbon,Str};
use Illuminate\Support\Facades\{Crypt,DB,Validator};
use Illuminate\Validation\ValidationException;

/** Documento pequeno cifrado no banco: artefato e auditoria têm o mesmo commit. */
final class GeracaoExportacaoTitular
{
    public const LIMITE_BYTES = 16384;

    public function __construct(private AutorizacoesExportacaoTitular $autorizacoes) {}

    private function evento(Usuario $u,string $id,Estado $estado): void
    {
        DB::table('pedido_exportacao_artefato_eventos')->insert([
            'artefato_id'=>$id,'ator_id'=>$u->id,'acao'=>$estado->value,'created_at'=>now(),
        ]);
    }

    private function resposta(object $a): array
    {
        return ['referencia'=>$a->id,'situacao'=>$a->situacao,
            'expira_em'=>Carbon::parse($a->expira_em)->utc()->toIso8601String()];
    }

    public function gerar(Usuario $u,string $autorizacao,array $dados): array
    {
        abort_unless(config('pedidos_titulares.geracao_exportacao'),403);
        if (array_diff(array_keys($dados),['versao','chave'])) {
            throw ValidationException::withMessages(['exportacao'=>'Campos não permitidos.']);
        }
        $d=Validator::make($dados,['versao'=>'required|integer|min:1','chave'=>'required|uuid'])->validate();
        return $this->autorizacoes->comAutorizacao($u,$autorizacao,function(array $c)use($u,$autorizacao,$d){
            $g=DB::table('pedido_exportacao_autorizacoes')->where('id',$autorizacao)->lockForUpdate()->first();
            abort_unless((int)$g->versao===(int)$d['versao'],409,'Autorização alterada.');
            $chave=['acesso_id'=>$u->acessoInternoContextoId(),'acesso_versao'=>$u->acessoInternoContextoVersao(),'chave'=>$d['chave']];
            $anterior=DB::table('pedido_exportacao_artefatos')->where($chave)->lockForUpdate()->first();
            if ($anterior) {
                abort_unless($anterior->autorizacao_id===$autorizacao && (int)$anterior->autorizacao_versao===(int)$d['versao'],409,'Chave já utilizada.');
                abort_unless(Estado::from($anterior->situacao)->disponivelNoPrazo(now()->toDateTimeImmutable(),
                    Carbon::parse($anterior->expira_em)->toDateTimeImmutable()),403);
                return $this->resposta($anterior);
            }
            abort_if(DB::table('pedido_exportacao_artefatos')->where('autorizacao_id',$autorizacao)->lockForUpdate()->first(),409,'Autorização já utilizada para geração.');
            $id=(string)Str::uuid();$estado=Estado::Solicitada;
            DB::table('pedido_exportacao_artefatos')->insert($chave+[
                'id'=>$id,'autorizacao_id'=>$autorizacao,'autorizacao_versao'=>$d['versao'],
                'situacao'=>$estado->value,'expira_em'=>$c['valida_ate'],'created_at'=>now(),'updated_at'=>now(),
            ]);
            $this->evento($u,$id,$estado);
            $estado=$estado->transicionar(Estado::Gerando);
            DB::table('pedido_exportacao_artefatos')->where('id',$id)->update(['situacao'=>$estado->value]);
            $this->evento($u,$id,$estado);

            // Lista positiva: nenhuma serialização integral do model ou campos HTTP.
            abort_unless($c['formato']==='cadastro_minimo_v1' && $c['campos'] && !array_diff($c['campos'],['nome','matricula']),403);
            $campos=array_values(array_unique($c['campos']));sort($campos);
            $aluno=DB::table('alunos')->where('id',$c['aluno_id'])->where('espaco_id',$c['espaco_id'])
                ->select($campos)->lockForUpdate()->first();
            abort_unless($aluno,404);
            $conteudo=(array)$aluno;
            $jsonDados=json_encode($conteudo,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $documento=json_encode(['manifesto'=>[
                'formato'=>$c['formato'],'referencia'=>$id,'autorizacao'=>$autorizacao,
                'gerado_em'=>now()->utc()->toIso8601String(),
                'expira_em'=>Carbon::parse($c['valida_ate'])->utc()->toIso8601String(),
                'campos'=>$campos,'sha256_dados'=>hash('sha256',$jsonDados),
            ],'dados'=>$conteudo],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            abort_if(strlen($documento)>self::LIMITE_BYTES,422,'Conteúdo excede o limite do formato.');
            $cifrado=Crypt::encryptString($documento);$hash=hash('sha256',$cifrado);
            DB::table('pedido_exportacao_artefatos')->where('id',$id)->update(['conteudo_cifrado'=>$cifrado,'sha256_cifrado'=>$hash]);
            $gravado=DB::table('pedido_exportacao_artefatos')->where('id',$id)->lockForUpdate()->first();
            abort_unless($gravado && hash_equals($hash,hash('sha256',(string)$gravado->conteudo_cifrado))
                && hash_equals($hash,(string)$gravado->sha256_cifrado),503,'Gravação da exportação indisponível.');

            // O relógio pode avançar durante a geração. Nunca publicar após o prazo.
            $this->autorizacoes->comAutorizacao($u,$autorizacao,function(array $atual)use($c){
                abort_unless($atual===$c && Carbon::parse($c['valida_ate'])->isFuture(),403);
            });
            $estado=$estado->transicionar(Estado::Disponivel);
            DB::table('pedido_exportacao_artefatos')->where('id',$id)->update(['situacao'=>$estado->value,'updated_at'=>now()]);
            $this->evento($u,$id,$estado);
            abort_unless(Carbon::parse($c['valida_ate'])->isFuture(),403);
            return $this->resposta((object)['id'=>$id,'situacao'=>$estado->value,'expira_em'=>$c['valida_ate']]);
        });
    }
}
