# 055 — Acompanhamento pedagógico — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** Onda 2, depois de FN-056 (agenda) e FN-039 (períodos letivos). O §5 é PROPOSTA.
**Natureza:** nova (só o visual demonstrativo existe)
**Origem:** ramo de TU1-R3 (Atenção e Top 5) pelos painéis de turmas — docs/177 P6, docs/179, docs/180
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir que o profissional parta de uma evidência de avaliação, planeje uma ação (intervenção, recuperação, encaminhamento), registre o retorno e acompanhe metas e decisões de conselho, sem que as conclusões ultrapassem os dados disponíveis.

## 2. Escopo
- **Inclui (docs/177 P6):**
  - P6.1 Habilidades e descritores: cadastro e versionamento, associação de itens com revisão e proveniência, habilidades planejadas por turma, disciplina e período, mapas habilidade × turma e estudante × habilidade.
  - P6.2 Intervenções e recuperação: necessidade, evidências, estudantes, responsável, estratégia, prazo e situação; seleção no gráfico vira proposta com revisão humana; reavaliação preservando a avaliação original.
  - P6.3 Cruzamentos e ordenações: participação × resultado, turma × disciplina, planejado × avaliado, intervenção × reavaliação, comparações longitudinais.
  - P6.4 Conselho e metas: visão de conselho, decisões, responsáveis, prazos, metas com situação inicial e critério.
- **Não inclui:**
  - Agenda das ações (P6.2.3) → FN-056; é proibido criar um segundo calendário.
  - Períodos letivos (P6.1.3, P6.3.1 via P4.4) → FN-039.
  - Associação automática de itens a habilidades (GII) → FN-044.
  - Indicação de recuperação já registrada no painel (TU1) → FN-053; ela não é plano de intervenção (P6.2.6).
  - Avisos e lembretes → FN-057.

## 3. Fontes de verdade
- Documentos de origem: docs/177 (`docs/177-plano-paineis-turmas.md`) (P6.1–P6.4), docs/179 (`docs/179-execucao-paineis-turmas.md`), docs/180 (`docs/180-correcao-dashboards-diretriz.md`) (tabela 5.1).
- Sem mockup original; os modelos visuais são os cards demonstrativos dos painéis (`habilidades`, `intervencoes`, `reavaliacoes`, `metas`, `conselho`, `devolutivas` em `config/paineis.php`).
- Casos de uso propostos: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`); novas chaves a definir.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-056 agenda-calendario | Ações e prazos na agenda única (P6.2.3) | Pendente (transversal) |
| FN-039 periodos-letivos | Planejamento por período; participação × resultado (P4.4) | Candidata |
| FN-053 paineis-turmas | Cards, catálogo e indicadores de origem | Parcial |
| FN-054 paineis-editor | Inclusão dos novos cards no catálogo | Concluída |
| FN-038 disciplinas-curriculo | Disciplinas e áreas (área não equivale a habilidade) | Em homologação |
| FN-026 matricula-enturmacao | Acompanhamento longitudinal dos mesmos estudantes | Parcial |
| FN-044 acervo-gii | Opcional, para P6.1 automatizado | Candidata |

**Funcionalidades que dependem desta:** nenhuma identificada.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 Modelos visuais | Cards demonstrativos de habilidades, intervenções, reavaliação, metas, conselho, devolutivas | "Em breve" com dados demonstrativos | Entregue (homologação, visual) | PR #211–#214 |
| 1.1 (P6.1) Habilidades e planejamento | Cadastro, associação, mapas | Evidência mínima definida | Pendente | docs/177 |
| 1.2 (P6.2) Intervenções e recuperação | Registro e acompanhamento da ação | Ação separada do resultado observado | Pendente | docs/177; depende de FN-056 |
| 1.3 (P6.3) Cruzamentos e ordenações | Cruzamentos com base explícita | Sem inferência causal | Pendente | docs/177; depende de FN-039 |
| 1.4 (P6.4) Conselho e metas | Visão de conselho e metas | Acesso restrito a observações reservadas | Pendente | docs/177 |

## 6. Contrato técnico
A definir antes da execução: modelo de dados (habilidades versionadas, intervenções, metas, decisões de conselho), migrations aditivas, permissões novas, auditoria de observações reservadas (P6.4.4) e tratamento de dados pessoais de menores (FN-062). Nenhuma tabela ou rota existe hoje.

## 7. Interface
Cards do painel da Turma e de Turmas que hoje são demonstrativos passam a reais, um a um, conforme cada fonte (P6.4.5). Estados vazio e "Em breve" conforme a regra vigente. Sem prints; conferências por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | — | Aprovar §1–§3 e Onda 0 | Pendente |
| §4 item 7 — escopo da agenda, PoC do CalendarJS e períodos letivos | Aprovar a PoC; priorizar FN-039 junto com FN-056 | Seguir a recomendação: FN-055 depende dos dois | Pendente |
| Regra da reavaliação (P6.2.4) | Substitui / complementa / compõe a nota | Definir no detalhamento de 1.2 | Pendente |
| Habilidades sem GII | Cadastro manual primeiro / aguardar FN-044 | Cadastro manual (P6.1.2) | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
