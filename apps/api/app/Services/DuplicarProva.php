<?php

namespace App\Services;

use App\Models\{Prova, Usuario};
use App\Support\{ContextoVinculo, ContextoCorrecao, Escopo, Espacos, Permissao};
use Illuminate\Support\Facades\DB;

/** CP1 v1: configuração e gabarito; enunciados/questões do futuro editor são outro domínio. */
class DuplicarProva
{
    public static function elegivel(Usuario $ator, Prova $prova): bool
    {
        if (!Espacos::ativo() || !ContextoVinculo::presente($ator)) return false;
        $v = ContextoVinculo::resolver($ator);
        if (!in_array($v->perfil, ['professor','professor_autonomo'], true)
            || $prova->formato_avaliacao !== 'gabarito'
            || (int)$prova->criado_por !== (int)$ator->id || (int)$prova->espaco_id !== (int)$v->espaco_id) return false;
        foreach (['prova.duplicar_propria','prova.ver','prova.criar','prova.editar','gabarito.ver','gabarito.editar'] as $p) {
            if (!Permissao::pode($ator, $p)) return false;
        }
        return ContextoCorrecao::provaValida($prova);
    }

    public function executar(Usuario $ator, int $origemId, string $titulo, string $chave): Prova
    {
        abort_unless(Espacos::ativo() && ContextoVinculo::presente($ator), 403);
        return DB::transaction(function () use ($ator, $origemId, $titulo, $chave) {
            DB::table('usuario_vinculos')->where('id',$ator->vinculoContextoId())->lockForUpdate()->firstOrFail();
            $origem = Escopo::provas($ator)->whereKey($origemId)->lockForUpdate()->first();
            abort_unless($origem && self::elegivel($ator,$origem),404,'Prova não disponível para cópia.');
            $hash = hash('sha256',json_encode([$origemId,$titulo],JSON_THROW_ON_ERROR));
            $anterior = DB::table('prova_duplicacoes')->where('vinculo_id',$ator->vinculoContextoId())->where('chave',$chave)->first();
            if ($anterior) {
                abort_unless(hash_equals($anterior->payload_hash,$hash),409,'Esta solicitação já foi utilizada com outros dados.');
                return Prova::findOrFail($anterior->prova_id);
            }
            ContextoCorrecao::exigirFilhos('gabaritos','prova_id',$origemId,$origem->espaco_id);
            $gabarito = DB::table('gabaritos')->where('prova_id',$origemId)->where('espaco_id',$origem->espaco_id)->lockForUpdate()->first();
            if ($gabarito) ContextoCorrecao::exigirFilhos('gabarito_questoes','gabarito_id',$gabarito->id,$origem->espaco_id);
            $campos = $origem->only(['formato_avaliacao','escola_id','disciplina','serie','num_questoes','num_alternativas','nota_maxima','tipo_pontuacao','anular_se_todas','opcao_lingua','gerar_cartao_pdf']);
            $nova = new Prova($campos + ['titulo'=>$titulo,'status'=>'rascunho','criado_por'=>$ator->id]);
            $nova->forceFill(Espacos::autoria((int)$origem->espaco_id,$ator)+['espaco_tipo'=>$origem->espaco_tipo]);
            $nova->save();
            if ($gabarito) {
                $autoria = ContextoCorrecao::autoria($origem->espaco_id,$ator->id);
                $novoGabarito = DB::table('gabaritos')->insertGetId(['prova_id'=>$nova->id,'created_at'=>now(),'updated_at'=>now()]+$autoria);
                $questoes = DB::table('gabarito_questoes')->where('gabarito_id',$gabarito->id)->where('espaco_id',$origem->espaco_id)->orderBy('numero_questao')->get();
                foreach ($questoes as $q) DB::table('gabarito_questoes')->insert([
                    'gabarito_id'=>$novoGabarito,'numero_questao'=>$q->numero_questao,'alternativa'=>$q->alternativa,
                    'alternativa_espanhol'=>$q->alternativa_espanhol,'peso'=>$q->peso,'anulada'=>$q->anulada,
                ]+$autoria);
            }
            $procedencia = DB::table('prova_duplicacoes')->where('prova_id',$origemId)->where('espaco_id',$origem->espaco_id)->first();
            DB::table('prova_duplicacoes')->insert(['prova_id'=>$nova->id,'origem_id'=>$origemId,
                'raiz_id'=>$procedencia?->raiz_id ?? $origemId,'autor_original_id'=>$procedencia?->autor_original_id ?? $origem->criado_por,
                'ator_id'=>$ator->id,'vinculo_id'=>$ator->vinculoContextoId(),'espaco_id'=>$origem->espaco_id,
                'chave'=>$chave,'payload_hash'=>$hash,'contrato_versao'=>1,'created_at'=>now()]);
            return $nova;
        });
    }
}
