<?php
namespace App\Support;

use App\Models\Usuario;

/** Identificadores e geometria apenas. Nenhum exemplo, dado pedagógico ou concessão. */
final class CatalogoWidgetsPainel
{
    public static function para(Usuario $ator, string $painel): array
    {
        $permissoes = Permissao::efetivas($ator);
        if (!in_array('turma.ver', $permissoes, true)) return [];
        $admin = PerfisEscolares::administrativo($ator->perfil);
        $pedagogico = !$admin && in_array('relatorio.turma', $permissoes, true);
        $lista = [];
        $adicionar = static function (string $id, string $section, bool $kpi = false) use (&$lista): void {
            $minH = $kpi ? 4 : ($id === 'alunos-recorte' ? 10 : 7);
            $defaultH = $id === 'turma-cadastro' ? 5 : ($kpi ? 4 : ($id === 'alunos-recorte' ? 12 : 9));
            $maxH = $id === 'turma-cadastro' ? 12 : ($kpi ? 8 : 20);
            $lista[] = ['id'=>$id, 'section'=>$section,
                'desktop'=>['minW'=>$kpi ? 2 : 4, 'maxW'=>12, 'minH'=>$minH, 'maxH'=>$maxH, 'defaultW'=>$kpi ? 3 : 6, 'defaultH'=>$defaultH],
                'mobile'=>['minW'=>12, 'maxW'=>12, 'minH'=>$minH, 'maxH'=>$maxH, 'defaultW'=>12, 'defaultH'=>$defaultH]];
        };
        if ($painel === 'turmas') {
            foreach (['kpi-turmas','kpi-em-uso','kpi-arquivadas','kpi-cadastros'] as $id) $adicionar($id, 'resumo', true);
            $adicionar('cadastros-turno', 'analises');
            if ($pedagogico) {
                foreach (['comparacao-turmas','cobertura-disciplinas'] as $id) $adicionar($id, 'analises');
                foreach (['proximos-compromissos','devolutivas','responsabilidade-docente-aplicacao','prioridades'] as $id) $adicionar($id, 'planejamento');
                $adicionar('intervencoes', 'ensino');
            }
        } elseif ($painel === 'turma') {
            if ($admin) $adicionar('turma-cadastro', 'resumo', true);
            if ($pedagogico) {
                foreach (['kpi-avaliacoes','kpi-resultados','kpi-alunos-resultado','kpi-media','kpi-mediana','kpi-faixa'] as $id) $adicionar($id, 'resumo', true);
                foreach (['distribuicao-acertos','resultados-avaliacao','situacoes-registradas'] as $id) $adicionar($id, 'analises');
                if (in_array('aluno.ver', $permissoes, true) && in_array('resultado.ver', $permissoes, true)
                    && !in_array($ator->perfil, ['aluno','aplicador'], true)) $adicionar('alunos-recorte', 'analises');
                foreach (['periodos-escolares','agenda-pedagogica','devolutivas','participacao','responsabilidade-docente-aplicacao'] as $id) $adicionar($id, 'planejamento');
                foreach (['habilidades','intervencoes','reavaliacoes'] as $id) $adicionar($id, 'ensino');
                $adicionar('metas', 'decisoes');
                if (in_array('turma.vincular_professor', $permissoes, true)) $adicionar('conselho', 'decisoes');
            }
        }
        if ($admin) {
            $adicionar('agenda-administrativa', 'administrativo');
            if (in_array('aluno.ver', $permissoes, true)) $adicionar('documentos-administrativos', 'administrativo');
        }
        return $lista;
    }
}
