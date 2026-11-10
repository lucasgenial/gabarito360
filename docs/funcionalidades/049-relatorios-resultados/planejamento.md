# 049 — Resultados e relatórios consolidados — Planejamento

**Estado no tronco:** Concluída
**Prioridade no plano mestre:** fora das ondas (funcionalidade concluída). A pendência "relatório por tema" depende de FN-043, que está em "Futuro" (§5, PROPOSTA).
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/13 MP-022 e MP-034; hotfix do PDF em lote (docs/123)
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Mostrar aos educadores e gestores o desempenho por aluno, prova, turma, escola, núcleo e rede, com análise por questão e documentos em PDF para imprimir ou compartilhar.

## 2. Escopo
- **Inclui:** resultado individual do aluno na prova (MP-022); relatório da prova e da turma na prova com KPIs e análise por questão; relatório consolidado da turma em todas as provas; relatório do aluno (boletim) em todas as provas; relatórios consolidados de escola, núcleo e rede com comparativos, destaques e atenção (MP-034); PDF do resultado e PDF em lote da prova ou turma (hotfix docs/123).
- **Não inclui:**
  - Folha digitalizada, histórico de tentativas, percentuais por questão, PDF pedagógico e validação pública → FN-047.
  - Relatório PDF do painel da turma com paridade ao painel → FN-050.
  - Relatório por tema → FN-043 (banco de questões).
  - TCT/TRI, integridade e sínteses → FN-051.
  - Consolidado da secretaria (`relatorio.secretaria`) → painel da secretaria em FN-052.

## 3. Fontes de verdade
- Mockups: `mockups/resultado.html`, `mockups/relatorio-prova.html`, `mockups/relatorio-turma-prova.html`, relatórios dos dashboards ([docs/01](../../01-inventario-dos-mockups.md) seções 20, 22, 23).
- Documentos de origem: [docs/13](../../13-roadmap.md) (MP-022, MP-034), [docs/04](../../04-requisitos-funcionais.md) RF-008, [docs/123](../../123-resultados-pdf-lote-hotfix.md), [docs/27](../../27-plano-graficos-echarts.md) (G2, gráficos dos relatórios).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-005, UC-006 e UC-010 do [docs/07](../../07-casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) §4.6.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-045 omr-correcao | Notas e respostas lidas | Concluída |
| FN-033 estrutura-escolar | Escolas, núcleos, turmas e alunos | Parcial |
| FN-021 clientes-espacos | Consultas por espaço (`ConsultasPedagogicas`) | Concluída |
| FN-071 marca-documentos | Cabeçalhos dos PDFs (`App\Support\Cabecalho`) | Em homologação |
| FN-043 editor-provas-questoes | Temas das questões para o relatório por tema | Candidata |

**Funcionalidades que dependem desta:** FN-047 (resultado ampliado), FN-050 (PDF da turma), FN-051 (análises avançadas), FN-052 (atalhos dos painéis), FN-053 (indicadores da turma), FN-067 (portal dos responsáveis).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (MP-022) Resultado individual e relatório da prova | Resultado, relatório da prova e turma × prova | Telas e PDF do resultado | Entregue (produção) | commit 7e0af6e |
| 1.2 (MP-034) Escola, núcleo e rede + PDF | Consolidados com comparativo | Relatórios por nível em produção | Entregue (produção) | commit f995232 |
| 1.3 PDF em lote (hotfix) | Um PDF por prova ou turma | Lote até 500 resultados | Entregue (produção) | PR #156; 83dd5ae |
| 2.1 Relatório por tema | Acertos por tema | Gráfico por tema no resultado e no relatório | Pendente | depende de FN-043 |

## 6. Contrato técnico (proporcional ao risco)
- **API**: `GET v1/relatorios/prova/{id}` e `relatorios/turma/{turmaId}/prova/{provaId}` (`relatorio.prova`); `relatorios/turma/{turmaId}` e `relatorios/aluno/{alunoId}` (`relatorio.turma`); `relatorios/escola|nucleo|rede/{id?}` (`relatorio.escola|nucleo|rede`); `alunos/{id}/resultados/{provaId}` (`resultado.ver`) e `/pdf` (`resultado.compartilhar`); `provas/{id}/resultados/pdf` (`relatorio.prova` + `resultado.compartilhar`, 5/min). Controladores `RelatorioController`, `RelatorioConsolidadoController`, `ResultadoController`.
- **Web**: `RelatorioController` (`relatorios.*`), `ResultadoController` (`resultados.*`); vistas em `resources/views/relatorios` e `resources/views/resultados`.
- **Escopo**: consultas por `ConsultasPedagogicas` (com histórico nos relatórios de prova, turma e aluno); provas por `Escopo::provaHistoricaComConteudo` (nunca aplicador). O escopo nunca é ampliado por permissão extra (docs/03 §1, regra 5).
- **Meta**: média da rede em `redes.meta_media`; padrão 7,0.
- **Consolidados**: notas na escala 0–10 para comparar provas diferentes; sem id, usam a unidade do usuário; com mais de uma, 422 pedindo seleção.
- **PDFs**: gerados por `omr/gerar_resultado.py`; exceção documentada à regra "API só JSON".

## 7. Interface
Telas de resultado, relatório da prova, turma × prova, turma, aluno e consolidado, com gráficos ECharts (FN-070). Sem prints; conferências por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre | — | Aprovar §1–§3 e Onda 0 | Pendente |
| §4 item 13 — frentes "Em breve" sem plano (inclui editor e versões, base do relatório por tema) | Manter "Em breve" só do que for priorizado | Seguir a recomendação do §4 | Pendente |
| ⚠ Divergência código × docs/03: `relatorio.rede` é padrão do Secretário de Educação (docs/03 §4.6, nota 3; `CatalogoPermissoes`), mas `RelatorioConsolidadoController::rede` devolve "Rede não encontrada." para qualquer perfil diferente de `admin_rede` | Ajustar o código ao docs/03 / ajustar o docs/03 | Decidir junto com o painel da secretaria (FN-052); não alterado nesta documentação | Pendente |

## 9. Ramos desta entrega
O hotfix do PDF em lote (docs/123) foi ramo desta funcionalidade e terminou com ela.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
