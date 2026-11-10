# 040 — Provas e gabaritos — Planejamento

**Estado no tronco:** Concluída
**Prioridade no plano mestre:** sem onda (entregue em produção); pendência única: confirmar o stepper do MP-037 (§3)
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/13 MP-019, MP-020; docs/19 MP-037
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Permitir que professores e coordenação criem provas objetivas, preencham e publiquem o gabarito oficial e deixem a prova pronta para a geração de cartões e a correção OMR. É o núcleo do ciclo de avaliação.

## 2. Escopo
- **Inclui:** lista de provas com busca e progresso; criação em passos (Dados → Gabarito oficial → Cartão e turmas); edição, publicação e exclusão de rascunho; associação de turmas; editor de gabarito (alternativa, alternativa de espanhol, peso, anulação); publicação do gabarito; campo `formato_avaliacao` (valor único `gabarito`).
- **Não inclui:**
  - Escopo por autoria, "Criada por" e "Meu espaço" → [FN-041](../041-autoria-provas/planejamento.md).
  - Cópia de prova → [FN-042](../042-duplicacao-provas/planejamento.md).
  - Configuração curricular (disciplinas por ID) → [FN-038](../038-disciplinas-curriculo/planejamento.md).
  - Cartões-resposta, envio e correção OMR → FN-045, FN-046, FN-047.
  - Resultados e relatórios → FN-049.
  - Banco de questões, enunciados, versões, discursivas e formatos digital/misto → [FN-043](../043-editor-provas-questoes/planejamento.md).
  - Marcação de data e aplicação na agenda → FN-059.

## 3. Fontes de verdade
- Mockups: `mockups/provas.html`, `criar-prova.html`, `gabarito.html` ([docs/01](../../01-inventario-dos-mockups.md) §08–§10).
- Documentos de origem: [docs/13](../../13-roadmap.md) MP-019, MP-020; [docs/19](../../19-plano-telas-por-perfil.md) MP-037; docs/04 RF-006; [docs/06](../../06-regras-de-negocio.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-003 de [docs/07](../../07-casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) — `prova.ver`, `prova.criar`, `prova.editar`, `gabarito.ver`, `gabarito.editar`.
- Plano mestre: [§2 FN-040](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-033 estrutura-escolar | Escolas e turmas da prova | Parcial (base entregue) |
| FN-012 permissoes-motor | Chaves de prova e gabarito | Concluída |
| FN-021 clientes-espacos | `espaco_id` e autoria de espaço | Concluída |

**Funcionalidades que dependem desta:** FN-041, FN-042, FN-043, FN-045, FN-046, FN-047, FN-049, FN-053, FN-059.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (MP-019) | CRUD de provas com stepper de criação | Criar, editar, publicar, excluir rascunho | Entregue (produção) | commit 2546b1b |
| 1.2 (MP-020) | Editor de gabarito, publicação | Gabarito completo publicado | Entregue (produção) | commit ca2c408 |
| 1.3 (MP-037) | Ciclo completo do professor; stepper Dados → Gabarito → Cartão | Fluxo contínuo dos três passos | Entregue (produção); stepper Incerto no plano mestre | commit 59a80ba; `provas/create.blade.php` |

## 6. Contrato técnico (proporcional ao risco)
- **API:** `GET/POST /v1/provas`, `GET/PUT/DELETE /v1/provas/{id}`, `POST /v1/provas/{id}/publicar`; `GET/POST /v1/provas/{id}/gabarito`, `POST /v1/provas/{id}/gabarito/publicar`.
- **Escopo:** `Escopo::provas` (consulta), `Escopo::provaEditavel` (escrita); o aplicador nunca recebe conteúdo (`provaComConteudo`).
- **Estados da prova:** `rascunho`, `publicada`, `em_correcao`, `corrigida`. Publicar o gabarito muda a prova para `publicada`.
- **Limites:** 1–100 questões; 3, 4 ou 5 alternativas; nota máxima 0,5–100; pontuação `igual` ou `personalizado`.
- **Preservação:** só rascunho é excluído; rascunho com histórico registrado é preservado (409).
- **Desempenho:** lista sem paginação na API → FN-074; carga → FN-077.

## 7. Interface
- Web: `provas.index`, `provas.create`, `provas.show`, `provas.update`, `provas.publicar`, `provas.destroy`; `provas.gabarito.show|edit|salvar|publicar`.
- Stepper visual de três passos presente em `provas/create.blade.php`, `provas/gabarito-edit.blade.php` e `provas/show.blade.php`.
- Estados e tabelas conforme FN-076 e FN-074.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e Onda 0 | Pendente |
| Stepper do MP-037 "Incerto" no plano mestre: o código mostra o stepper de três passos nas telas de criação, gabarito e detalhe | Confirmar na homologação / dar por confirmado | Conferência textual do fluxo e atualização do plano mestre | Pendente |
| Passo 3 "Cartão e turmas" não detalhado no mockup (docs/07 UC-003) | — | Comportamento do código vale até novo mockup | Registrado |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
