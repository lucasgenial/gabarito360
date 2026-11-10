# 029 — Escola independente / autônoma (EI1) — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** Onda 4 — Comercial, portal e encerramento (EI1-E depende de FN-068)
**Natureza:** nova
**Origem:** surgiu na entrega H1 (identidade e cabeçalhos) em 05/11/2026 — docs/163, 176
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir que uma escola contrate e use o sistema diretamente, com cliente, espaço e equipe próprios, sem exigir rede ou núcleo fictícios. A "escola autônoma" fica isolada da instância superior até que esta assuma formalmente a responsabilidade financeira.

## 2. Escopo
- **Inclui:** EI1-A inventário de constraints, triggers, consultas e contratação dependentes de rede/núcleo; EI1-B cadastro e edição de escola independente, instituição superior e seleção/solicitação de rede; EI1-C esquema compatível, separando associação institucional, contrato e autorização; EI1-D solicitação e conferência de redes pelo suporte, com trilha; EI1-E adesão privada direta, encaminhamento da escola pública e liberação excepcional auditada; EI1-F provisionamento de cliente, espaço e equipe; EI1-G testes de isolamento e homologação.
- **Não inclui:**
  - Regras financeiras da adesão e da assunção de custeio → FN-068.
  - Espaço pessoal do professor → FN-022 / FN-027 (não unificar com "escola autônoma").
  - Catálogo de instituições e pedidos de inclusão → FN-031 (reutilizado).
  - Cabeçalhos de documentos por contexto → FN-071.
  - Novos perfis: os cargos seguem a matriz aprovada (FN-014); não criar superperfil.
  - Encerramento → FN-028; transferências → FN-025.

## 3. Fontes de verdade
- Documentos de origem: docs/163 (`docs/163-escolas-independentes.md`), docs/176 (`docs/176-reconciliacao-solicitacoes.md`); sequência em docs/165.
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). Relacionado no docs/07: UC-002 (Cadastrar Escola).
- Mockups: cadastro de escola do inventário (docs/01); as variações da modalidade independente exigem revisão UI/UX prévia (EI1-B).
- A home pública anuncia o recurso como "Em breve" (FN-072).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-068 financeiro | EI1-E: adesão, assunção de responsabilidade financeira, período já pago | Pendente |
| FN-023 cadastro-clientes | Cliente tipo escola; provisionamento (EI1-F) | Concluída |
| FN-021 clientes-espacos | Espaço tipo escola; hoje herda o cliente do núcleo | Concluída |
| FN-031 catalogo-instituicoes | INEP, UF/município, verificação de duplicidade, solicitações ao suporte | Em homologação |
| FN-033 estrutura-escolar | `escolas.nucleo_id` obrigatório no esquema e no formulário | Parcial |
| FN-013 acessos-internos | Suporte/Desenvolvedor criam redes e liberam exceções | Concluída |

**Funcionalidades que dependem desta:** nenhuma declarada no plano mestre.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (EI1-A) Inventário | Constraints, triggers, fontes, autorização e consultas dependentes de rede/núcleo | Inventário detalhado | Parcial (diagnóstico inicial) | docs/163 |
| 1.2 (EI1-B) Cadastro e edição | Escola independente, instituição superior, seleção/solicitação de rede | Revisão UI/UX prévia aprovada | Pendente | docs/163 |
| 1.3 (EI1-C) Esquema | Hierarquia não obrigatória; três dimensões separadas | Migration aditiva; dados atuais intactos | Pendente | docs/163 |
| 1.4 (EI1-D) Redes pelo suporte | Solicitação, conferência e trilha de alterações institucionais | Escola não cria rede | Pendente | docs/163 |
| 1.5 (EI1-E) Adesão | Privada direta; pública encaminhada; exceção auditada | Regras financeiras definidas em FN-068 | Pendente | docs/163 |
| 1.6 (EI1-F) Provisionamento | Cliente, espaço e equipe com permissões existentes | Sem acesso por pagamento | Pendente | docs/163 |
| 1.7 (EI1-G) Testes e homologação | Isolamento, edição sem bypass, matriz pública/privada, dados antigos | Homologação validada | Pendente | docs/163 |

## 6. Contrato técnico (proposto)
- **Situação atual:** existem cliente e espaço do tipo `escola`, mas `escolas.nucleo_id` é obrigatório e `Instituicoes::criarEspaco` herda o cliente do núcleo; não há criação de escola sem hierarquia.
- **Proposto:** associação institucional, cliente/contrato e autorização de acesso como dimensões independentes; associar à rede não dá aos gestores dela acesso ao espaço da escola; concessões explícitas compatíveis com o isolamento por espaço; mudanças relevantes prospectivas e auditadas, sem reescrever documentos emitidos.
- **Migração:** aditiva; nenhuma escola atual convertida automaticamente.

## 7. Interface
Cadastro pergunta se a escola integra uma rede; rede e núcleo não são obrigatórios na modalidade independente; seleção restrita a registros conferidos ou solicitação ao suporte. Cabeçalhos: escola sem rede só com a sua logo. Estados de carga, vazio e erro (FN-076). Revisão UI/UX antes de EI1-B.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 13 — frentes "Em breve" da home sem plano (inclui escola autônoma) | Manter "Em breve" só para o priorizado | A do plano mestre | Pendente |
| §4 item 14 — regras financeiras de adesão da EI1 | Tratar em FN-068 | A do plano mestre | Pendente |
| Alcance e vigência da assunção de responsabilidade financeira; período já pago (regra aprovada: não devolver valores); encerramento posterior | — | Especificar em FN-068 antes de EI1-E | Pendente |
| Escola pública que insiste em contratar diretamente | Liberação explícita de Suporte/Desenvolvedor com motivo | Regra do docs/163 | Aprovada (docs/163) |
| Sequência: docs/163 ("após H1") × docs/165 ("UR1 → TU1 → EI1"); nunca retomada | — | Vale o plano mestre (Onda 4) | Pendente |

## 9. Ramos desta entrega
Nenhum (não iniciada).

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
