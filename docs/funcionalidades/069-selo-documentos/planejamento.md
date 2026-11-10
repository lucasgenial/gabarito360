# 069 — Selo dos documentos — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** Onda 4 (PROPOSTA do §5): depois de FN-068; depende de decisão comercial
**Natureza:** nova
**Origem:** tronco original — docs/28 R1 (Fase 5). Equivalência: R1 (selo dos documentos, docs/28) → FN-069; não confundir com R1–R3 das fotos leves (docs/25), que são FN-046 ([plano mestre §1.2](../../planejamento/00-plano-mestre.md))
**Atualizado em:** 2026-11-10, por lucasgenial

## 1. Objetivo
Exibir, nas impressões e nos PDFs, a divulgação do Gabarito360 conforme a modalidade comercial do cliente: cliente presenteado mostra um bloco institucional com QR; cliente pagante mostra apenas "Feito no gabarito360.com.br". A regra é única e central, sem dados do aluno.

## 2. Escopo
- **Inclui:** regra central `Selo::do(espaco)`; componente `components/selo-documento.blade.php`; CSS de impressão; aplicação a impressões e PDFs de todos os relatórios e aos geradores `omr/gerar_resultado.py` e `omr/gerar_cartoes.py`; mesmo texto para todos; o bloco não aparece na tela.
- **Não inclui:**
  - Cabeçalhos e marca oficial dos documentos (`<x-marca>`, identidade nos relatórios) → [FN-071](../071-marca-documentos/planejamento.md).
  - Definição e alteração da modalidade do cliente → [FN-023](../023-cadastro-clientes/planejamento.md); contratos, cortesia e cobrança → [FN-068](../068-financeiro/planejamento.md).
  - Impressão e PDF do portal dos responsáveis (P6) → [FN-067](../067-portal-responsaveis/planejamento.md), que consome este selo.
  - Relatórios em si → [FN-049](../049-relatorios-resultados/planejamento.md) e [FN-050](../050-relatorio-turma-pdf/planejamento.md).

## 3. Fontes de verdade
- Mockups: não há mockup do selo; os relatórios seguem `mockups/relatorio-prova.html`, `relatorio-turma-prova.html` e `resultado.html` (docs/01 (`docs/01-inventario-dos-mockups.md`)). O desenho do bloco precisa de aprovação.
- Documentos de origem: docs/28 (`docs/28-plano-mestre-proximos-modulos.md`) R1 e a decisão já definida ("Cliente presenteado exibe a divulgação do Gabarito360 em impressões e PDFs. Cliente pagante exibe apenas 'Feito no gabarito360.com.br'"); docs/161 (`docs/161-identidade-e-cabecalhos.md`) (resolvedor de identidade e partial de impressão compartilhado).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-010 do docs/07 (`docs/07-casos-de-uso.md`) (exportar PDF) é o fluxo em que o selo aparece.
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) — o selo não cria permissão; vale para quem já pode imprimir ou exportar.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-023 cadastro-clientes (T4) | `clientes.modalidade` (`pagante`, `presenteado`, `teste`, `interno` em `Cliente::MODALIDADES`) | Concluída |
| FN-021 clientes-espacos | Resolver o cliente a partir do espaço | Concluída |
| FN-068 financeiro | Ordem da Onda 4; a cortesia/`presenteado` passa a ser definida pelo contrato | Pendente |
| FN-071 marca-documentos | Partial de impressão e resolvedor de identidade onde o selo se encaixa | Em homologação |
| Decisão comercial (§4 item 14) | Texto e desenho do bloco de divulgação | Pendente |

**Funcionalidades que dependem desta:** FN-067 portal-responsaveis (P6, impressão e PDF com selo).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (R1) Selo dos documentos | Regra central, componente, CSS de impressão, aplicação a relatórios e geradores OMR | Testes cobrem as duas modalidades; impressão conferida em A4 | Pendente | Nenhum commit; os "R1" do histórico Git são das fotos leves (docs/25) |

## 6. Contrato técnico (proporcional ao risco)
- Regra única no servidor, a partir do espaço do documento; nenhuma tela decide o selo por conta própria.
- A API continua sem renderizar telas: ela informa a variante do selo; a web e os geradores OMR desenham.
- Nos cartões-resposta, o selo não pode alterar marcadores, QR, campos e posições usados pela leitura (docs/161); exige teste de leitura (FN-045/FN-047) antes de publicar.
- Sem dados do aluno no bloco; mesmo texto para todos.
- Sem tabela nova prevista; se houver, migration aditiva.

## 7. Interface
Bloco só em impressão e PDF (`@media print` e geradores), nunca na tela; A4 retrato e paisagem; conferência registrada por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e a Onda 0 | Pendente |
| §4 item 14 — condições de lançamento comercial | — | Tratar junto com FN-068; define o texto do bloco | Pendente |
| Modalidades `teste` e `interno` | Tratar como presenteado / pagante / sem selo | Levar ao responsável; o docs/28 só define presenteado e pagante | Pendente |
| Espaço pessoal do professor autônomo | Selo pela modalidade do cliente do espaço pessoal | Confirmar com FN-068 | Pendente |
| Destino do QR do bloco institucional | Página pública / apresentação | Definir com FN-072 | Pendente |
| Risco de regressão na leitura OMR ao tocar `gerar_cartoes.py` | — | Posicionar fora das áreas de leitura e testar com cartão impresso | Registrado |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
