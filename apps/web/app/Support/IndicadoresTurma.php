<?php
namespace App\Support;

/** Explicações de leitura; não recalcula notas, permissões ou a população recebida. */
final class IndicadoresTurma
{
    private static function contagem(mixed $v): ?int { return is_int($v) && $v >= 0 ? $v : null; }

    public static function base(mixed $validos, mixed $total, mixed $excluidos): string
    {
        $n=self::contagem($validos);$t=self::contagem($total);$e=self::contagem($excluidos);
        if($n===null || $t===null || $e===null || $n+$e!==$t) return 'A base completa e as exclusões não foram informadas nesta consulta.';
        return "Base: {$n} de {$t} resultados; {$e} sem percentual válido.";
    }

    public static function ajudas(array $d, ?string $consulta, bool $indisponivel=false): array
    {
        $ids=['kpi-avaliacoes','kpi-resultados','kpi-alunos-resultado','kpi-media','kpi-mediana','kpi-faixa','distribuicao-acertos','resultados-avaliacao','situacoes-registradas','alunos-destaques','alunos-atencao'];
        if($indisponivel) return array_fill_keys($ids,['Não foi possível consultar os indicadores. Nenhum valor foi confirmado.']);
        $k=$d['kpis']??[];$a=$d['analise']??[];$r=$a['resumo']??[];
        $base=self::base($r['n']??null,$k['resultados']??null,$a['distribuicao_acertos']['percentuais_excluidos']??null);
        $peso='Cada resultado válido tem o mesmo peso. Avaliações com bases diferentes não representam comparação de proficiência.';
        $recorte='Resultados concluídos da turma no recorte autorizado; cartões em revisão não entram nesta base.';
        $ajudas=[
            'kpi-avaliacoes'=>['Avaliações aplicadas no recorte autorizado. Uma avaliação sem resultado concluído continua nesta contagem.'],
            'kpi-resultados'=>[$recorte,'Um aluno pode ter um resultado em cada avaliação.'],
            'kpi-alunos-resultado'=>['Cadastros distintos de alunos com resultado concluído no recorte. Não é uma contagem de pessoas entre instituições.'],
            'kpi-media'=>['Média simples dos percentuais de acertos válidos de cada resultado.',$base,$peso],
            'kpi-mediana'=>['Valor central dos percentuais de acertos válidos.',$base],
            'kpi-faixa'=>['Menor e maior percentual de acertos válido observado no recorte.',$base],
            'distribuicao-acertos'=>[$recorte,'Cada resultado com percentual válido entra em uma única faixa.',$base],
            'resultados-avaliacao'=>['Média e mediana por avaliação, com a base própria de cada instrumento.',$peso,'Avaliação completa no recorte desta turma: selecionar uma disciplina não divide a nota por questões.'],
            'situacoes-registradas'=>['Usa a situação registrada no resultado de cada avaliação; não recalcula o critério pela porcentagem de acertos.','Inclui resultados concluídos sem percentual válido. Base: '.(self::contagem($d['situacoes_resultados']['total']??null)??'não informada').' resultados.'],
            'alunos-destaques'=>['Até cinco cadastros de alunos com maior média simples dos percentuais válidos no recorte. Um resultado válido tem um peso.','Empates mantêm a mesma posição pelo percentual apresentado; pode haver outros alunos empatados no corte. Alunos sem percentual válido não entram.'],
            'alunos-atencao'=>['Alunos com ao menos um resultado com indicação de recuperação registrada no recorte. Não é diagnóstico.','O percentual de cada aluno é a média simples dos seus percentuais válidos. Alunos sem resultado não entram.'],
        ];
        foreach($a['por_avaliacao']??[] as $p){
            if(!is_array($p)||!is_string($p['titulo']??null))continue;
            $ajudas['resultados-avaliacao'][]=$p['titulo'].': '.self::base($p['n']??null,$p['resultados_total']??null,$p['percentuais_excluidos']??null);
        }
        $momento=$consulta ? 'Consultado em '.$consulta.'. Isso indica a consulta, não a última alteração dos dados.' : 'Momento da consulta não informado.';
        foreach($ajudas as &$linhas)$linhas[]=$momento;
        return $ajudas;
    }
}
