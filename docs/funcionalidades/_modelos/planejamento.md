# <NNN> — <Nome da funcionalidade> — Planejamento

**Estado no tronco:** <Candidata | Priorizada | Em planejamento | Planejamento aprovado | Em execução | Em homologação | Concluída | Adiada | Substituída>
**Prioridade no plano mestre:** <posição/onda>
**Natureza:** <nova | existente documentada a partir do código | evolução de existente>
**Origem:** <tronco original (docs/13 MP-xxx, docs/28 pacote) | surgiu na entrega <x> em <data>>
**Atualizado em:** <AAAA-MM-DD>, por <agente/pessoa>

## 1. Objetivo
O problema resolvido e para quem, em 2 ou 3 frases.

## 2. Escopo
- **Inclui:** ...
- **Não inclui:** ... Cada item fora do escopo aponta para a funcionalidade do tronco que o cobre.

## 3. Fontes de verdade
Mockups (`mockups/...`), style system, documentos de origem (`docs/NN-...`), casos de uso ([casos-de-uso.md](casos-de-uso.md)) e perfis e permissões (`docs/03`).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|

**Funcionalidades que dependem desta:** ... São as que aguardam esta funcionalidade para receber suas mudanças.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 | | | Pendente | |

Status: Pendente, Em execução, Entregue (homologação), Concluída, Adiada, Substituída. Evidência é PR, CI, revisão e recibo de publicação.

## 6. Contrato técnico (proporcional ao risco)
API e BFF (UR4), modelo de dados e migrations aditivas, autorização e revogação, volume e paginação, cache ou decisão de não usar, filas e idempotência, desempenho com o padrão de 1000 usuários simultâneos quando aplicável.

## 7. Interface
Telas, estados (carga, vazio, erro), acessibilidade, desktop e celular, e gates de UI/UX. A conferência é registrada por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|

## 9. Ramos desta entrega
Subdivisões técnicas criadas durante a execução, que terminam com ela. Algo que não seja só desta entrega vai para o plano mestre como candidata.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
