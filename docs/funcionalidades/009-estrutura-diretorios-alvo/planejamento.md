# 009 — Estrutura-alvo de diretórios — Planejamento

**Estado no tronco:** Candidata
**Prioridade no plano mestre:** Futuro (candidatas sem prioridade); aguardando priorização — não é executada
**Natureza:** nova
**Origem:** surgiu na entrega ST1 (estabilização das turmas), docs/168, em 11/2026
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Reorganizar o monorepo para a estrutura aceita pelo responsável — `apps/web/`, `apps/api/plataforma/` e diretórios próprios para motores futuros — de modo que novos motores (por exemplo GII e TRI) tenham lugar próprio sem misturar responsabilidades com a API da plataforma.

## 2. Escopo
- **Inclui (preliminar):**
  - Mover a API atual de `apps/api/` para `apps/api/plataforma/`.
  - Reservar diretórios para os motores futuros.
  - Ajustar CI, roteiros de deploy (produção e homologação), unidades systemd, Nginx e documentação aos novos caminhos.
- **Não inclui:**
  - Criar os motores → FN-044 (acervo GII) e FN-051 (análises avançadas).
  - Separação física de serviços → [FN-007](../007-escala-infraestrutura/planejamento.md) (X5).
  - Aplicativos móveis (`apps/android`, `apps/ios`) → [FN-010](../010-app-mobile/planejamento.md).

## 3. Fontes de verdade
- Mockups: não se aplica.
- Documentos de origem: [docs/168](../../168-estabilizacao-turmas-e-contratos.md) ("Estrutura alvo aceita pelo responsável"; "ST1 não move diretórios nem cria motores"), [docs/12](../../12-arquitetura.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: não se aplica.
- Plano mestre: [§2 FN-009](../../planejamento/00-plano-mestre.md), §4 item 15.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| Decisão §4 item 15 | Virar plano ou ser arquivada | Pendente |
| FN-044 acervo-gii / FN-051 analises-avancadas | Um segundo motor é o que justifica a mudança | Candidatas |
| FN-003 publicacao-e-homologacao | Roteiros com caminhos fixos (`apps/api`, `apps/web`) | Concluída |
| FN-008 ci-integracao | Workflow com caminhos fixos | Concluída |

**Funcionalidades que dependem desta:** nenhuma declarada. FN-044 e FN-051 podem vir a depender, se a estrutura for adotada antes delas.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 9.1 Decisão: plano ou arquivamento | Decisão registrada | Decisão do responsável | Pendente | Estrutura aceita no docs/168, sem plano e sem execução |
| 9.2 Plano de migração de estrutura, CI e operação | `planejamento.md` detalhado | Aprovado pelo responsável | Pendente | Só se 9.1 decidir pelo plano |

## 6. Contrato técnico (proporcional ao risco)
A detalhar se priorizada. Pontos já conhecidos: a mudança não altera contrato HTTP nem banco; afeta todos os caminhos em `.github/workflows/verificacao.yml`, `scripts/operacao/deploy.sh` (valida `apps/api` e `apps/web`), `infra/homologacao/deploy.py`, `infra/systemd/*` e Nginx; exige uma publicação própria, com janela, em homologação e depois em produção.

## 7. Interface
Sem interface.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 15 — estrutura-alvo de diretórios | Planejar agora; manter candidata; arquivar | Manter como candidata até haver segundo motor (GII/TRI) que a justifique | Pendente |
| Risco operacional de mover a API com entregas em andamento | Janela dedicada; migração junto com o primeiro motor | Não executar em paralelo a outras entregas | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
