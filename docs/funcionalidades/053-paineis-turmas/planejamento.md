# 053 — Painéis de turmas e da turma — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Prioridade no plano mestre:** Onda 2 (P3.3, P1.4.6, P3.4), depois de FN-026 e FN-059. O que já foi entregue está na fila de produção (§7, bloco "Painéis e editor"). O §5 é PROPOSTA.
**Natureza:** existente documentada a partir do código
**Origem:** ramo de TU1/TU1-R3 (FN-036) e da reconciliação do docs/176; virou módulo próprio com numeração local P0–P8 (docs/177)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Oferecer dois painéis — o agregado de Turmas e o de cada Turma — que mostrem, conforme a permissão de quem consulta, o resumo cadastral e os resultados das avaliações com bases e exclusões explícitas, sem inventar indicadores que os dados não sustentam.

## 2. Escopo
- **Inclui:** P0 reconciliação e base executável; P1 contratos (catálogo de componentes, projeção por permissão, filtros, semântica dos indicadores, preferências por escopo); P2 composição visual, Tabela padrão + Cards e modelos "Em breve" com dados demonstrativos; P3 dados reais (resumo cadastral, indicadores atuais, painel agregado, estados por região); P8.2–P8.4 validação contínua.
- **Não inclui:**
  - Mover, redimensionar e editor hierárquico → FN-054.
  - Períodos letivos (P4) → FN-039. Agenda e avisos (P5) → FN-056 e FN-057; os cards atuais são demonstrativos e não podem virar agenda local.
  - Acompanhamento pedagógico (P6) → FN-055. GII (P7.1) → FN-044. TCT/TRI, integridade, sínteses (P7.2–P7.4) → FN-051.
  - Relatório PDF com paridade (P8.1) → FN-050.
  - Registro de aplicação e elegíveis (domínio de aplicação) → FN-059, com FN-026.
  - Cadastro e organização da turma → FN-036.

## 3. Fontes de verdade
- Mockups: `mockups/turmas.html`, `mockups/turma-detalhe-2.html` (docs/01 (`docs/01-inventario-dos-mockups.md`) seções 13 e 14); direção de design em `docs/evidencias/paineis/`.
- Documentos de origem: docs/176 (`docs/176-reconciliacao-solicitacoes.md`), docs/177 (`docs/177-plano-paineis-turmas.md`) (plano P0–P8), docs/178 (`docs/178-protocolo-execucao-paineis.md`), docs/179 (`docs/179-execucao-paineis-turmas.md`), docs/180 (`docs/180-correcao-dashboards-diretriz.md`), docs/182 (`docs/182-p32-indicadores.md`), docs/183 (`docs/183-paineis-estados-vazios.md`), [contratos/resumo-cadastral-turmas.md](../../contratos/resumo-cadastral-turmas.md). Em conflito entre os blocos "Decisão mais recente" dos docs/177–180, vale o fim do docs/179 (PR #216) e o plano mestre (§6).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-007 do docs/07 (`docs/07-casos-de-uso.md`).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) (`turma.ver`, `relatorio.turma`, `correcao.acompanhar`).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-036 organizacao-turma | Tela da turma e resumo cadastral | Em homologação |
| FN-014 perfis-institucionais (PF1) | Projeção por permissão e perfis administrativos | Concluída |
| FN-038 disciplinas-curriculo (DC1) | Filtro por disciplina e currículo das provas | Em homologação |
| FN-026 matricula-enturmacao (MT1) | Elegíveis por aplicação (P1.4.6) e histórico | Parcial |
| FN-059 agendamento-aplicacao-provas | Registro de aplicação (P1.4.6, P3.3.5) | Pendente |
| FN-056 agenda-calendario / FN-057 notificacoes | Cards reais de agenda e de avisos | Pendente (transversais) |
| FN-039 periodos-letivos | Seletor de período (P4) | Candidata |
| FN-076 e FN-077 | Carregamento regional (UI1-R) e desempenho (P3.4) | Parciais |

**Funcionalidades que dependem desta:** FN-054 (usa a infraestrutura de preferências, P1.5), FN-050 (bases de P3.2), FN-055 (cards de acompanhamento), FN-051 (pontos de integração).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 (P0) Reconciliação e base executável | Matriz e base | — | Entregue (homologação) | PR #210 |
| 1.1 (P1.1, P1.3, P1.5) Catálogo, projeção por permissão, preferências por escopo | `turma.ver` não entrega dados pedagógicos | Revisão independente registrada | Entregue (homologação) | PR #210, #211 |
| 1.2 (P1.2) Filtros e vínculos | Professor, disciplina, período; autor × aplicador × docente | Filtros relacionados e históricos | Parcial | docs/179 |
| 1.3 (P1.4) Semântica dos indicadores | Estudantes únicos × matrículas × participações | Bases explícitas em todos os cards | Parcial | docs/179; PR #220 |
| 1.4 (P1.4.6) Elegíveis por aplicação | Registro histórico dos elegíveis | Taxa de participação com base real | Pendente | depende de FN-026 e FN-059 |
| 2.1 (P2.1–P2.13) Composição e modelos demonstrativos | Dois painéis, Tabela + Cards, "Em breve" | Aprovado no PR #214 | Entregue (homologação) | PR #211–#214 |
| 2.2 (P2.4) Variantes por perfil | 7 composições por perfil | Fechamento por perfil registrado | Incerto | — |
| 3.1 (P3.1, P3.2 e ajuste) Cadastros, resumo e indicadores atuais | `resumo_cadastral`, bases, exclusões, popover | Indicadores reais na turma | Entregue (homologação) | PR #215, #220, #221 |
| 3.2 (P3.3) Painel agregado de turmas | Comparação por instrumento, numeradores e denominadores | Sem somar médias de turmas | Pendente | próximo pacote canônico do docs/182 |
| 3.3 (P3.4) Estados por região | Falha de um bloco não derruba os demais | UI1-R nas regiões do painel | Parcial | F2/F4 (FN-077) |
| 8.1 (P8.2–P8.4) Validação contínua | Validação de dados, revisão visual, homologação gradual | A cada PR | Contínua | — |

## 6. Contrato técnico (proporcional ao risco)
- **API**: `GET v1/turmas/{id}/painel` (`relatorio.turma`), filtros `disciplina_id`, `professor_id` (autor original da prova), `inicio`, `fim`, `analise_prova_id`; `GET v1/turmas` e `GET v1/turmas/{id}` trazem o resumo cadastral e o bootstrap do painel. Serviços `PainelTurma` (DTO único para tela e PDF), `AnalisePainelTurma`, `CatalogoWidgetsPainel`, `CatalogoPaineis`; configuração em `config/paineis.php` (painéis `turmas`, recurso de contexto, e `turma`, recurso turma; seção fixa `resumo`).
- **Web**: `TurmaController::index/show/secao` (`turmas.index`, `turmas.show`, `turmas.secao`), parciais `turmas/_painel*`, `_resumo_cadastral`, `_modelos`.
- **Autorização**: `PainelTurma::montar` exige `relatorio.turma` e nega perfis administrativos (403); a turma vem de `Escopo::turmas`; os dados operacionais da correção (revisão, processamento, falhas, não identificados) só aparecem com `correcao.acompanhar`. A configuração de painéis não é matriz de autorização.
- **Regras de dados**: só provas publicadas, em correção ou corrigidas com data de aplicação até hoje; só notas de cartão vigente `lido`; percentual só com `total_questoes > 0` e acertos coerentes; situações "dentro do critério" e "indicação de recuperação" pelo `status_aprovacao`.
- **Cache**: `CacheComposicaoPainel` para a estrutura; sem cache de concessões (P3.4.5).
- **Desempenho**: F2 e F4 (FN-077) cobrem consultas e carga regional; medição com 1000 usuários simultâneos pendente.

## 7. Interface
Painel de Turmas (resumo, tabela padrão com Cards alternativos, modelos demonstrativos) e painel da Turma (resumo cadastral, "Resultados da turma", filtros em modal compacto com recorte discreto, cards sem dados com altura reduzida). Cards sem fonte real usam "Em breve" com "Dados demonstrativos" (docs/180). Sem prints; conferências por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | — | Aprovar §1–§3 e Onda 0 | Pendente |
| §4 item 2 — gate de produção e fila acumulada | Promover em blocos / manter | Promover depois do bloco de segurança | Pendente |
| §4 item 7 — escopo da agenda e PoC do CalendarJS; períodos letivos | Aprovar a PoC e priorizar FN-039 com FN-056 | Seguir a recomendação: os painéis precisam do recorte por período | Pendente |
| §4 item 8 — identidades para a carga de 1000 usuários simultâneos | Contas sintéticas só na VM 120 | Seguir a recomendação (P3.4.3 depende da medição) | Pendente |
| P2.4 "Incerto" | Conferir e fechar por perfil / replanejar | Conferir na homologação e registrar por texto | Pendente |
| Filtro "Professor autor" | Manter / renomear com novo contrato histórico (P1.2.3) | Manter até existir contrato de docente responsável | Pendente |

## 9. Ramos desta entrega
Viraram funcionalidades próprias: FN-054 (editor), FN-039 (períodos), FN-055 (acompanhamento), FN-050 (PDF), além dos cards de agenda e avisos que aguardam FN-056 e FN-057.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
