<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Projeção fechada de material. Não classifica textos livres como anônimos. */
final class MaterialCopia
{
    public const POLITICA = 'material-1';
    public const CAMPOS = ['titulo','disciplina','serie','num_questoes','num_alternativas','nota_maxima',
        'tipo_pontuacao','anular_se_todas','opcao_lingua'];

    public static function projetar(int $espaco, int $id): array
    {
        $p=Espacos::conferirEscola(DB::table('provas'),'provas')->where('espaco_id',$espaco)->where('id',$id)->first();
        abort_unless($p,404,'Material indisponível.');
        $qtd=(int)$p->num_questoes; $alternativas=(int)$p->num_alternativas;
        abort_unless($qtd >= 1 && $qtd <= 100 && in_array($alternativas,[3,4,5],true)
            && in_array($p->tipo_pontuacao,['igual','personalizado'],true)
            && in_array((int)$p->anular_se_todas,[0,1],true) && in_array((int)$p->opcao_lingua,[0,1],true)
            && is_numeric($p->nota_maxima) && (float)$p->nota_maxima > 0
            && (float)$p->nota_maxima <= 999.99,422,'Configuração de material inválida.');
        foreach (['titulo','disciplina','serie'] as $campo) abort_unless(is_string($p->$campo) && trim($p->$campo) !== '',422,'Texto de material incompleto.');
        $g=DB::table('gabaritos')->where('prova_id',$id)->first();
        abort_unless($g && (int)$g->espaco_id === $espaco,422,'Gabarito indisponível.');
        $questoes=DB::table('gabarito_questoes')->where('gabarito_id',$g->id)->orderBy('numero_questao')->get();
        abort_unless($questoes->count() === $qtd,422,'Gabarito incompleto.');
        $permitidas=array_slice(['A','B','C','D','E'],0,$alternativas); $saida=[]; $pesoTotal=0;
        foreach ($questoes as $i=>$q) {
            $espanhol=(bool)$p->opcao_lingua && $i < 5;
            abort_unless((int)$q->espaco_id === $espaco && (int)$q->numero_questao === $i+1
                && in_array($q->alternativa,$permitidas,true)
                && ($espanhol ? in_array($q->alternativa_espanhol,$permitidas,true) : $q->alternativa_espanhol === null)
                && in_array((int)$q->anulada,[0,1],true)
                && is_numeric($q->peso) && (float)$q->peso >= 0 && (float)$q->peso <= 999.99,422,'Gabarito incompatível.');
            $pesoTotal+=(float)$q->peso;
            $saida[]=['numero_questao'=>$i+1,'alternativa'=>$q->alternativa,'alternativa_espanhol'=>$q->alternativa_espanhol,
                'peso'=>number_format((float)$q->peso,2,'.',''),'anulada'=>(bool)$q->anulada];
        }
        abort_if($p->tipo_pontuacao === 'personalizado' && $pesoTotal <= 0,422,'Peso total inválido.');
        $material=array_intersect_key((array)$p,array_flip(self::CAMPOS));
        foreach (['num_questoes','num_alternativas'] as $campo) $material[$campo]=(int)$material[$campo];
        foreach (['anular_se_todas','opcao_lingua'] as $campo) $material[$campo]=(bool)$material[$campo];
        $material['nota_maxima']=number_format((float)$material['nota_maxima'],2,'.','');
        // Ordem estável independente de ALTER TABLE/ordem física das colunas.
        $ordenado=[]; foreach (self::CAMPOS as $campo) $ordenado[$campo]=$material[$campo];
        return ['prova'=>$ordenado,'questoes'=>$saida];
    }
}
