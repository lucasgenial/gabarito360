<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{Cadastro,ContextoInterno,ContextoVinculo,Espacos,GestaoVinculos,Permissao,RegimeVinculos};
use Illuminate\Support\Facades\DB;

/** Saída explícita de um vínculo institucional. Não transfere nem exclui dados. */
final class EncerrarVinculoProfessor
{
    public function executar(Usuario $gestor,int $vinculo): void
    {
        abort_unless(Espacos::ativo() && RegimeVinculos::obrigatorio() && ContextoVinculo::presente($gestor)
            && !ContextoInterno::presente($gestor),403,'Gestão indisponível.');
        DB::transaction(function() use($gestor,$vinculo) {
            DB::table('usuario_vinculos')->where('id',$gestor->vinculoContextoId())->lockForUpdate()->first();
            ContextoVinculo::resolver($gestor);
            abort_unless(Permissao::pode($gestor,'usuario.gerir'),403,'Gestão indisponível.');
            $v=GestaoVinculos::consulta($gestor)->where('v.id',$vinculo)->select('v.*')->lockForUpdate()->first();
            abort_unless($v && $v->perfil==='professor' && (int)$v->usuario_id!==(int)$gestor->id
                && Cadastro::podeGerirPerfil($gestor,$v->perfil),403,'Vínculo indisponível.');
            if ($v->situacao==='encerrado') return;
            abort_unless(in_array($v->situacao,['ativo','suspenso'],true),422,'Vínculo não pode ser encerrado por esta operação.');
            DB::table('usuario_vinculos')->where('id',$vinculo)->update(['situacao'=>'encerrado','fim_em'=>now()]);
            GestaoVinculos::evento($v,$gestor,'encerrado','saida_instituicao');
        });
    }
}
