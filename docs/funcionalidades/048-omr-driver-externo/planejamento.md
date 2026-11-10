# 048 — Driver OMR externo — Planejamento

**Estado no tronco:** Candidata
**Prioridade no plano mestre:** Futuro (candidatas sem prioridade, §5). Não executar antes da priorização do responsável.
**Natureza:** nova
**Origem:** tronco original — docs/08 ("Modo Externo (Futuro)" e "Escopo Futuro — OMR Externo") e docs/12 (DA-004; fase 6 pós-MVP)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir a correção de cartões-resposta produzidos por terceiros, com layout diferente do cartão nativo, sem alterar o restante do fluxo de correção.

## 2. Escopo preliminar
- **Inclui (proposto, docs/08):** cadastro do layout do cartão (regiões de interesse); número de questões e alternativas por região; upload de cartão de referência para calibração; ajuste do motor ao layout; validação com cartões de teste antes do uso; um novo driver que implemente `OmrDriverInterface` (hoje há `nativo` e `simulado`, escolhidos por `OMR_DRIVER` em `config/omr.php`). Alternativa citada no docs/08: serviço externo via API REST.
- **Não inclui:** leitura do cartão nativo → FN-045; barreira de qualidade e tentativas → FN-047 (que deverão valer também para o novo driver).

## 3. Fontes de verdade
[docs/08](../../08-omr.md), [docs/12](../../12-arquitetura.md) (DA-004, quadro de fases), [docs/18](../../18-omr-nativo-e-cartoes.md). Não há mockup para o cadastro de layout. Casos de uso propostos: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-045 omr-correcao | Interface `OmrDriverInterface` e fluxo de envio | Concluída |
| FN-047 omr-qualidade-resultado | A barreira de qualidade hoje exige os 4 marcadores do cartão nativo | Em homologação |
| FN-002 base-operacao | Fila assíncrona dos envios | Concluída |

**Funcionalidades que dependem desta:** nenhuma identificada.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 Plano | `planejamento.md` e `casos-de-uso.md` aprovados, mockup do cadastro de layout | Aprovação do responsável | Pendente | §3 do plano mestre ("Plano") |

## 6. Contrato técnico
A definir no plano. Pontos já conhecidos: a interface atual recebe só caminho, número de questões e de alternativas; identificação do aluno (QR) é feita por `OmrIdentificacaoInterface`, que um cartão de terceiros talvez não ofereça; a barreira de qualidade (`omr/qualidade.py`) é específica do layout nativo.

## 7. Interface
A definir (cadastro de layout e calibração). Sem mockup.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | — | Manter em "Futuro" | Pendente |
| Identificação do aluno sem QR em cartões de terceiros | Vinculação manual / leitura de matrícula marcada | Definir no plano | Pendente |
| Motor próprio × serviço externo | Driver Python local / API REST de terceiro | Definir no plano, considerando LGPD (envio de fotos a terceiros) | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
