# 038 — Disciplinas, áreas e currículo das provas (DC1) — Planejamento

**Estado no tronco:** Em homologação
**Prioridade no plano mestre:** sem onda própria; restante (validação do responsável, produção) na fila de produção §7 ("Disciplinas e currículo", PR #171, #186)
**Natureza:** existente documentada a partir do código
**Origem:** tronco — docs/115 §6, docs/139, docs/141–151, docs/156, docs/157
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Dar à escola um catálogo local de disciplinas e áreas do conhecimento, referenciado à BNCC, com componentes por turma e atribuições docentes com vigência, e ligar as provas a disciplinas reais (não a texto livre), de modo que a visibilidade do professor e os recortes dos painéis usem a organização curricular.

## 2. Escopo
- **Inclui:** referência nacional BNCC versionada (DC1-A); catálogo local e indicação de professores por disciplina (DC1-B); componente da turma e atribuições temporais titular/codocência/substituição (DC1-C); áreas do conhecimento N:N com vínculos temporais área–disciplina; autoria contextual da prova; integração curricular das provas (configuração por IDs, interseção entre turmas, revalidação na publicação); simulados ENEM de homologação; fechamento DC1-D.
- **Não inclui:**
  - Seleção em lote "área organiza disciplinas" → Substituída (docs/148).
  - Recurso genérico de institucionalização de provas → sem planejamento (docs/145).
  - Operação composta disciplina + professor na tela da turma → [FN-036](../036-organizacao-turma/planejamento.md) (consome DC1).
  - Períodos letivos (DC1 temporal é vínculo área–disciplina, não período) → [FN-039](../039-periodos-letivos/planejamento.md).
  - Cópia para professor (CP2), que usa a elegibilidade DC1 → [FN-042](../042-duplicacao-provas/planejamento.md).
  - Acervo GII → [FN-044](../044-acervo-gii/planejamento.md).

## 3. Fontes de verdade
- Sem mockup específico; telas aprovadas nas revisões de UI/UX do DC1 (docs/146, 157).
- Documentos de origem: [docs/139](../../139-id1-dc1-ativacao-e-sequencia.md), [docs/142](../../142-dc1-integracao-provas-e-cp2.md), [docs/144](../../144-dc1-areas-conhecimento.md), [docs/145](../../145-dc1-autoria-contextual-e-simulados.md), [docs/146](../../146-dc1-orientacoes-recolhidas.md), [docs/148](../../148-dc1-vinculos-temporais-de-areas.md), docs/141, 143, 147, 149–151, 156, [docs/157](../../157-revisao-painel-pdf.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) — `disciplina.gerir` (Coordenador, padrão).
- Plano mestre: [§2 FN-038](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-015 vinculos-contexto | Contexto do vínculo (escola, perfil) obrigatório | Concluída |
| FN-014 perfis-institucionais (PF1) | Vínculo Professor aceito para indicação | Concluída |
| FN-012 permissoes-motor | `disciplina.gerir`, `turma.vincular_professor` | Concluída |
| FN-021 clientes-espacos | Isolamento por `espaco_id` | Concluída |

**Funcionalidades que dependem desta:** FN-036 (aba Disciplinas e professores), FN-039 (usa DC1), FN-042 (CP2 exige elegibilidade DC1), FN-053 (filtro por disciplina nos painéis), FN-041 (visibilidade institucional do professor por atribuição), FN-040 (criação curricular de prova).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (DC1-A) | Referência BNCC versionada, isolamento, `disciplina.gerir` | Referências consultáveis | Entregue (homologação) | PR #171 (5966d21); docs/141 |
| 1.2 (DC1-B) | Catálogo local e indicação de professores | Coordenação mantém o catálogo | Entregue (homologação) | PR #171 |
| 1.3 (DC1-C) | Componente da turma e atribuições temporais | Vigência, histórico, inativação por turma | Entregue (homologação) | PR #171; docs/143 |
| 2.1 | Áreas do conhecimento N:N | Catálogo de áreas | Entregue (homologação) | PR #186 (commit 2c05fd0) |
| 2.2 | Vínculos temporais área–disciplina | Desvincular/revincular com confirmação quando há uso em prova | Entregue (homologação) | PR #186 (72c08fa); docs/148 |
| 2.3 | Autoria contextual da prova | Usuário + vínculo + perfil de criação imutáveis | Entregue (homologação) | PR #186; docs/145, 150 |
| 2.4 | Integração curricular das provas (API e formulário) | Revalidação nas três entradas de publicação | Entregue (homologação) | PR #186 (64f836d); docs/150 |
| 2.5 | Simulados ENEM e professores de teste (só homologação) | Comando restrito | Entregue (homologação) | `PrepararSimuladosHomologacao.php`; docs/156 |
| 2.6 (DC1-D) | Fechamento: testes, revisão UI/UX, CI, recuperação | Publicado na homologação | Entregue (homologação) | PR #186 merge 3738d04; publicado 273db8a; docs/157 |
| 2.7 | Seleção em lote "área organiza disciplinas" | — | Substituída | docs/148 |
| 2.8 | Recurso genérico de institucionalização | — | Sem planejamento | docs/145 |
| 3.1 | Validação do responsável | Registro da validação | Pendente | — |
| 3.2 | Produção | Decisão expressa | Pendente | §4 item 2 |

## 6. Contrato técnico (proporcional ao risco)
- **Ativação:** flag `DISCIPLINAS_ATIVAS` (`config/disciplinas.php`, padrão desligado); exige espaços ativos e vínculo de escola (`CurriculoProva::aplicavel`). Sem a flag, as rotas respondem 404.
- **Autorização (`GestaoDisciplinas::contexto`):** leitura para vínculos de escola Coordenador, Diretor Escolar, Vice-diretor e Professor com `turma.ver`; escrita só Coordenador com `disciplina.gerir` (403 "A gestão de disciplinas cabe à coordenação autorizada."). Atribuir também exige `turma.vincular_professor`.
- **API:** `/v1/disciplinas` (`opcoes`, `professores`, lista paginada, CRUD, `{id}/professores`); `/v1/areas-conhecimento` (`opcoes`, lista, CRUD, `{id}/vinculos`, `{id}/desvincular`); `/v1/turmas/{turma}/disciplinas` (lista, adicionar, `{componente}`, `situacao`, `atribuicoes`, `atribuicoes/{id}/encerrar`); `/v1/provas/opcoes-disciplinas` (GET/POST). Limite 60/min.
- **Concorrência:** `versao` obrigatória em edições; `chave` UUID nas atribuições; transação READ COMMITTED com retry de deadlock na integração curricular.
- **Dados:** migrations aditivas (disciplinas, áreas, `disciplina_areas` com períodos, `turma_disciplinas`, `disciplina_professores`, `docente_atribuicoes`, `prova_disciplinas`, campos de autoria em `provas`). Legado de texto livre preservado sem conversão automática.

## 7. Interface
- Web: `disciplinas.*` (lista, nova, detalhe, editar, indicar), `areas.*` (lista, nova, detalhe, editar, vincular/desvincular), componente da turma (`disciplinas.componente`, atribuir, situação, encerrar), currículo da prova (`provas.curriculo.*`, `provas.preparar`).
- Tabela compacta de vínculos de áreas (origem do padrão de FN-074).
- Busca e paginação reais; falha de consulta distinta de lista vazia.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — gate de produção (PR #171, #186 só na homologação) | Promoção em blocos / aguardar | Definir bloco após a segurança | Pendente |
| §4 item 6 — matriz DC1: docs/139 previa `disciplina.ver/gerir` e `lotacao.gerir`; o código usa só `disciplina.gerir` (leitura por perfil + `turma.ver`) | Registrar a matriz em vigor / criar as chaves | Registrar formalmente no docs/03 a matriz em vigor | Pendente |
| Vice-diretor gerir disciplinas (extra) | Conceder / não | Decidir junto com a matriz ID1 | Pendente |
| Recurso genérico de institucionalização | Planejar / descartar | Registrar como candidata se necessário | Pendente |

## 9. Ramos desta entrega
Encerrados: áreas N:N → vínculos temporais (pedidos durante a execução, entregues no PR #186); padrão de tabela compacta virou FN-074.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
