<?php

namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoInterno,EstadoExportacaoTitular};
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{Crypt,DB};

final class EntregaExportacaoTitular
{
    private const JSON = JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR;
    public function __construct(private AutorizacoesExportacaoTitular $autorizacoes) {}

    private function integro(bool $condicao): void
    {
        abort_unless($condicao,409,'Exportação indisponível para entrega.');
    }

    private function documento(object $a,array $c): array
    {
        $this->integro(is_string($a->conteudo_cifrado) && strlen($a->conteudo_cifrado)>0
            && strlen($a->conteudo_cifrado)<=65536 && is_string($a->sha256_cifrado)
            && hash_equals($a->sha256_cifrado,hash('sha256',$a->conteudo_cifrado)));
        try {
            $texto=Crypt::decryptString($a->conteudo_cifrado);
            $this->integro(strlen($texto)<=GeracaoExportacaoTitular::LIMITE_BYTES);
            $doc=json_decode($texto,true,16,JSON_THROW_ON_ERROR);
            $this->integro(is_array($doc) && array_keys($doc)===['manifesto','dados']
                && is_array($doc['manifesto']) && is_array($doc['dados'])
                && json_encode($doc,self::JSON)===$texto);
            $m=$doc['manifesto'];$campos=$c['campos'];sort($campos);
            $this->integro(array_keys($m)===['formato','referencia','autorizacao','gerado_em','expira_em','campos','sha256_dados']
                && $m['formato']===$c['formato'] && $m['referencia']===$a->id
                && $m['autorizacao']===$a->autorizacao_id && $m['campos']===$campos
                && array_keys($doc['dados'])===$campos);
            foreach ($doc['dados'] as $valor) $this->integro(is_string($valor) || $valor===null);
            $this->integro(is_string($m['sha256_dados']) && hash_equals($m['sha256_dados'],hash('sha256',json_encode($doc['dados'],self::JSON)))
                && is_string($m['gerado_em']) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/D',$m['gerado_em'])===1
                && $m['expira_em']===Carbon::parse($a->expira_em)->utc()->toIso8601String());
            $gerado=Carbon::parse($m['gerado_em']);
            $this->integro($gerado->toIso8601String()===$m['gerado_em'] && $gerado->lte(now())
                && $gerado->gte(Carbon::parse($a->created_at)) && $gerado->lte(Carbon::parse($a->updated_at))
                && $gerado->lt(Carbon::parse($a->expira_em)));
            return [$texto,$m];
        } catch (DecryptException|\JsonException|\Carbon\Exceptions\InvalidFormatException $e) {
            abort(409,'Exportação indisponível para entrega.');
        }
    }

    private function acessar(Usuario $u,string $id,bool $download): array
    {
        abort_unless(config('pedidos_titulares.entrega_exportacao'),403);
        $interno=ContextoInterno::resolver($u);
        abort_unless(in_array($interno->perfil,['desenvolvedor','suporte'],true),403);
        // Somente a referência da concessão é lida antes dos locks; nunca conteúdo.
        $ref=DB::table('pedido_exportacao_artefatos')->where('id',$id)->where('acesso_id',$interno->id)
            ->where('acesso_versao',$interno->versao_autorizacao)->value('autorizacao_id');
        abort_unless($ref,404);
        return $this->autorizacoes->comAutorizacao($u,$ref,function(array $c)use($u,$id,$ref,$interno,$download){
            $a=DB::table('pedido_exportacao_artefatos')->where('id',$id)->lockForUpdate()->first();
            $g=DB::table('pedido_exportacao_autorizacoes')->where('id',$ref)->lockForUpdate()->first();
            abort_unless($a && $a->autorizacao_id===$ref && (int)$a->acesso_id===(int)$interno->id
                && (int)$a->acesso_versao===(int)$interno->versao_autorizacao
                && (int)$a->autorizacao_versao===(int)$g->versao,404);
            abort_unless($a->expira_em && EstadoExportacaoTitular::tryFrom($a->situacao)?->disponivelNoPrazo(
                now()->toDateTimeImmutable(),Carbon::parse($a->expira_em)->toDateTimeImmutable())
                && Carbon::parse($a->expira_em)->lte(Carbon::parse($c['valida_ate'])),403);
            [$texto,$manifesto]=$this->documento($a,$c);
            DB::table('pedido_exportacao_artefato_eventos')->insert(['artefato_id'=>$id,'ator_id'=>$u->id,
                'acao'=>$download?'download_liberado':'consulta_validada','created_at'=>now()]);
            $this->autorizacoes->comAutorizacao($u,$ref,function(array $atual)use($c,$a){
                abort_unless($atual===$c && Carbon::parse($a->expira_em)->isFuture(),403);
            });
            $resposta=['referencia'=>$id,'situacao'=>$a->situacao,'formato'=>$manifesto['formato'],
                'campos'=>$manifesto['campos'],'expira_em'=>$manifesto['expira_em']];
            // Nenhum callback de streaming: os bytes retornam somente após o commit.
            return $download ? ['documento'=>$texto,'referencia'=>$id] : $resposta;
        });
    }

    public function consultar(Usuario $u,string $id): array {return $this->acessar($u,$id,false);}
    public function baixar(Usuario $u,string $id): array {return $this->acessar($u,$id,true);}
}
