# Dashboards, Resultados e Relatórios V2

## Dashboards canônicos

| Ator | Tela fonte | Conteúdo obrigatório |
|---|---|---|
| Administrador | `dashboard-admin.html`, `dashboard.html` | rede, escolas, usuários, atividade, alertas e visão executiva |
| Diretor de núcleo | `dashboard-diretor-nucleo.html` | escolas, comparativos, agenda, visitas e desempenho |
| Diretor escolar | `dashboard-diretor-escolar.html` | equipe, turmas, matrículas, provas, pendências e consolidado |
| Coordenador | `dashboard-coordenador.html` | provas, correções, turmas, desempenho e ações pedagógicas |
| Professor | `dashboard-professor.html` | próximas provas, turmas, alunos em atenção, correções e ações rápidas |
| Aluno | `dashboard-aluno.html` | próprias provas, resultados, evolução, avisos e perfil |

## Relatórios e resultados

- Resultado individual com nota, respostas, temas, comparativos e revisão.
- Relatório por prova com turmas, distribuição, questões e desempenho.
- Relatório turma/prova com alunos, média, pendências e detalhes.
- Consolidado por escola e núcleo.
- Ficha do aluno e relatórios visíveis em PDF.
- CSV/XLSX e exportação integral onde previstos na configuração.

## Regras

- Todo número deriva de consulta persistida e escopada.
- Indicadores exibem período, contexto e atualização.
- Relatórios assíncronos têm status e download temporário auditado.
- Reverb atualiza progresso; snapshot API corrige perda de eventos.
- Gráficos têm alternativa tabular acessível.
