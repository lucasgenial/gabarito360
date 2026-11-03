<?php
namespace App\Services;
use App\Models\Usuario;
use App\Support\{ContextoInterno,ContextoVinculo,Permissao};
use Illuminate\Support\Facades\DB;

final class PoliticaCatalogo
{
    public function interno(Usuario $u,bool $bloquear=false): object
    {
        abort_unless(config('catalogo.interface_ativa')===true,503,'Catálogo temporariamente indisponível.');
        if($bloquear) abort_unless(DB::table('acessos_internos_controle')->where('id',1)->lockForUpdate()->first(),503);
        $a=ContextoInterno::resolver($u);
        abort_unless(in_array($a->perfil,['suporte','desenvolvedor'],true) && ContextoInterno::pode($u,'catalogo.solicitacao.analisar'),403);
        if($bloquear) abort_unless(DB::table('usuarios')->where('id',$u->id)->where('ativo',true)->where('forcar_troca_senha',false)->lockForUpdate()->first(),403);
        return $a;
    }
    public function pessoal(Usuario $u,bool $bloquear=false): object
    {
        $v=app(ReferenciasInstituicoesPessoais::class)->contexto($u,$bloquear,'catalogo.solicitar');
        abort_unless(Permissao::pode($u,'catalogo.solicitar'),403);return $v;
    }
    public function fontes(): void { DB::table('catalogo_fontes')->orderBy('nome')->lockForUpdate()->get(); }
    public function gestor(Usuario $u,?string $unidade=null,bool $bloquear=false): object
    {
        abort_unless(config('catalogo.interface_ativa')===true && ContextoVinculo::presente($u),403);
        if($bloquear) {
            abort_unless(DB::table('usuarios')->where('id',$u->id)->where('ativo',true)->where('forcar_troca_senha',false)->lockForUpdate()->first(),403);
            $registro=DB::table('usuario_vinculos')->where('id',$u->vinculoContextoId())->where('usuario_id',$u->id)->lockForUpdate()->first();abort_unless($registro,403);
            $e=DB::table('espacos')->where('id',$registro->espaco_id)->lockForUpdate()->first();abort_unless($e,403);
            DB::table('clientes')->where('id',$e->cliente_id)->lockForUpdate()->first();
            DB::table('vinculo_permissoes')->where('vinculo_id',$registro->id)->lockForUpdate()->get();
            DB::table('perfil_permissoes')->where('perfil',$registro->perfil)->where('permissao','catalogo.manter_escopo')->lockForUpdate()->get();
        }
        $v=ContextoVinculo::resolver($u);
        abort_unless(in_array($v->perfil,['admin_rede','dir_nucleo','dir_escolar'],true) && Permissao::pode($u,'catalogo.manter_escopo'),403);
        if($unidade!==null) {
            $q=DB::table('catalogo_gestao_autoridades')->where('unidade_id',$unidade)->where('situacao','ativa')->whereIn('escola_id',ContextoVinculo::escolas($u));
            $autoridade=$bloquear?$q->lockForUpdate()->first():$q->first();abort_unless($autoridade,404,'Instituição fora do alcance autorizado.');
            $v->escola_autorizada_id=$autoridade->escola_id;
        }
        return $v;
    }
}
