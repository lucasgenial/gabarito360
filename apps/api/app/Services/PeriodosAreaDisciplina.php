<?php

namespace App\Services;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\{Crypt, DB};

/** Chamado sob a trava do espaço; a classificação não modifica acesso ou dados acadêmicos. */
final class PeriodosAreaDisciplina
{
    public static function abrir(int $espaco, int $area, int $disciplina): int
    {
        $q = DB::table('disciplina_areas')->where(['espaco_id'=>$espaco,'area_id'=>$area,'disciplina_id'=>$disciplina]);
        abort_if($q->exists(), 409, 'A disciplina já está vinculada. Atualize a tabela.');
        $id = DB::table('disciplina_area_vinculos')->insertGetId(['espaco_id'=>$espaco,'area_id'=>$area,'disciplina_id'=>$disciplina,'ativo'=>true,'versao'=>1,'vinculado_em'=>now()]);
        DB::table('disciplina_areas')->insert(['espaco_id'=>$espaco,'area_id'=>$area,'disciplina_id'=>$disciplina,'vinculo_id'=>$id]);
        return $id;
    }

    public static function encerrar(int $espaco, int $area, int $disciplina, int $ator, ?string $confirmacao = null): void
    {
        $q = DB::table('disciplina_areas')->where(['espaco_id'=>$espaco,'area_id'=>$area,'disciplina_id'=>$disciplina]);
        $corrente = (clone $q)->lockForUpdate()->first();
        abort_unless($corrente && $corrente->vinculo_id, 409, 'O vínculo mudou. Atualize a tabela.');
        $p = DB::table('disciplina_area_vinculos')->where('id',$corrente->vinculo_id)->lockForUpdate()->first();
        $usos = DB::table('prova_area_vinculos')->where('espaco_id',$espaco)->where('vinculo_id',$p->id)->orderBy('prova_id')->pluck('prova_id')->all();
        if ($usos) {
            $estado = ['espaco'=>$espaco,'periodo'=>$p->id,'versao'=>$p->versao,'ator'=>$ator,'uso'=>hash('sha256',json_encode($usos))];
            $aceita = false;
            if ($confirmacao) try {
                $lido = json_decode(Crypt::decryptString($confirmacao), true, 16, JSON_THROW_ON_ERROR);
                $aceita = ($lido['expira']??0) >= now()->timestamp && ($lido['estado']??null) === $estado;
            } catch (\Throwable) { /* Uma confirmação inválida nunca autoriza encerrar. */ }
            if (!$aceita) throw new HttpResponseException(response()->json([
                'message'=>'Confirme o encerramento deste vínculo. Os registros anteriores serão preservados.',
                'codigo'=>'confirmacao_desvinculacao',
                'data'=>['vinculo_id'=>$p->id,'provas'=>count($usos),'confirmacao'=>Crypt::encryptString(json_encode(['estado'=>$estado,'expira'=>now()->addMinutes(10)->timestamp]))],
            ], 409));
        }
        DB::table('disciplina_area_vinculos')->where('id',$p->id)->update(['ativo'=>false,'desvinculado_em'=>now(),'versao'=>$p->versao+1]);
        $q->delete(); // Índice corrente somente; o período e seus usos permanecem.
    }

    /** A integração das provas deve chamar esta operação na mesma transação da classificação. */
    public static function registrarUso(int $espaco, int $prova, int $periodo): void
    {
        DB::transaction(function () use ($espaco,$prova,$periodo) {
            DB::table('espacos')->where('id',$espaco)->lockForUpdate()->first();
            $p = DB::table('disciplina_area_vinculos')->where('espaco_id',$espaco)->where('id',$periodo)->lockForUpdate()->first();
            abort_unless($p && $p->ativo && DB::table('provas')->where('espaco_id',$espaco)->where('id',$prova)->exists(), 409, 'A classificação mudou. Confira a prova antes de salvar.');
            $q = ['espaco_id'=>$espaco,'prova_id'=>$prova,'vinculo_id'=>$periodo];
            if (!DB::table('prova_area_vinculos')->where($q)->exists()) DB::table('prova_area_vinculos')->insert($q+['registrado_em'=>now()]);
        }, 3);
    }
}
