# 046 — Fotos da correção e revisão de leituras — Planejamento

**Estado no tronco:** Parcial
**Prioridade no plano mestre:** sem onda própria. O hotfix de leituras 500 e a revisão (PR #182–#187) estão na fila de produção (§7) e entram na Onda 0 apenas se o responsável aprovar a decisão §4 item 2 (promoção em blocos). R3 (limiares) não tem onda. O §5 é PROPOSTA.
**Natureza:** existente documentada a partir do código
**Origem:** incidente de produção de 30/10/2026 (processos PHP esgotados) → docs/24 e docs/25; hotfixes e melhorias da revisão nos docs/152–157
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir que o educador confira e corrija as leituras do OMR olhando a foto do cartão, sem sobrecarregar o servidor com imagens pesadas, e sem expor as fotos por URL pública.

## 2. Escopo
- **Inclui:** fotos sob demanda e miniaturas (ações 1–4 do docs/24, R1); imagem endireitada e original servidas só a quem acessa a prova; tela de revisão com uma questão por vez e recorte da questão (R2); revisão de cartão já lido; cartão inteiro em modal acessível; imagem lateral protegida no desktop; leitor PDF lateral do cartão digitalizado (PDF.js e pdf-lib locais); ajuste futuro dos limiares de ambiguidade (R3).
- **Não inclui:**
  - Leitura, ambíguos, vínculo e fechamento → FN-045.
  - Barreira de qualidade, tentativas e histórico, PDFs verificáveis → FN-047.
  - Carregamento regional e breadcrumb nas demais telas → FN-076.
  - Miniaturas por visibilidade (F4-A) → FN-077.

## 3. Fontes de verdade
- Mockups: `mockups/acompanhar-correcao.html`, `mockups/resultado.html` (revisar leitura) — [docs/01](../../01-inventario-dos-mockups.md) seções 11 e 20.
- Documentos de origem: [docs/24](../../24-plano-fotos-sob-demanda.md), [docs/25](../../25-plano-revisao-de-leituras.md), [docs/152](../../152-incidente-revisao-leituras-500.md), [docs/153](../../153-cartao-inteiro-assinatura.md), [docs/154](../../154-revisao-cartao-modal.md), [docs/155](../../155-revisao-imagem-protegida.md), [docs/157](../../157-revisao-painel-pdf.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-004 (sub-fluxo de ambíguos) e UC-005 ("Revisar leitura") do [docs/07](../../07-casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) §4.5 e §5 ("fotos de cartões só a quem acessa a prova").

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-045 omr-correcao | Cartões, leituras e ambíguos | Concluída |
| FN-018 seguranca-urls-sessao | UR1/UR3 retiraram URLs assinadas e `_ctx`; imagens passaram a fetch autenticado | Em homologação |
| FN-002 base-operacao | Separação dos grupos de processos web e API desde 30/10 | Concluída |

**Funcionalidades que dependem desta:** FN-047 (OMR3-A/B reutiliza fotos com carregamento regional e o leitor PDF do docs/157).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (docs/24 ações 1–4; R1; R2) Fotos sob demanda, miniaturas, tela de revisão | Revisão questão a questão com recorte | Revisão operando em produção | Entregue (produção) | PR #37, #40, #41 |
| 1.2 (R3) Ajuste de limiares | Medir decisões reais e reduzir revisões desnecessárias | Limiar novo com medição registrada | Pendente | docs/25 |
| 1.3 Hotfix leituras 500 | Autorização de links em lote; agregação de notas | Sem erro 500 na revisão | Entregue (homologação); produção não autorizada | PR #182; docs/152 |
| 1.4 Cartão no modal, imagem protegida, leitor PDF lateral | Visualização do cartão inteiro na revisão | Revisão com cartão digitalizado ao lado | Entregue (homologação) | PR #184, #185, #187 |
| 1.5 Assinatura do cartão inteiro | Transportar `_ctx` sem alterar URL assinada | — | Entregue, depois obsoleta (UR1/UR3) | PR #183; docs/153 |

## 6. Contrato técnico (proporcional ao risco)
- **API**: `GET v1/provas/{id}/revisao` (fila: questões ambíguas da prova, ou todas as de um cartão com `?cartao=`), `POST v1/cartoes/{id}/revisar`, `GET v1/cartoes/{id}/imagens/{tipo}` (`original`, `miniatura`, `endireitada`; `Cache-Control: private, no-store`), `GET v1/cartoes/{id}/imagem`. A rota antiga `cartoes/{id}/arquivo/{tipo}` responde 404 ("uma assinatura na URL não substitui autenticação").
- **Web (BFF)**: `CorrecaoController::revisao/revisar/imagem`, `CartaoImagemController` (`cartoes.imagem-segura`), vistas `correcao/revisao.blade.php`, `_documento`, `_leitura-questoes`.
- **Imagens**: `App\Services\Omr\ImagensCartao` gera miniatura e endireitada e calcula o recorte de cada questão.
- **Autorização**: `correcao.acompanhar` para ver; `correcao.alterar` para revisar; aplicador nunca vê imagens.
- **Desempenho**: o hotfix (docs/152) reduziu cerca de 47 mil para cerca de 1,4 mil consultas na revisão; medições com o padrão de 1000 usuários simultâneos seguem pendentes em FN-077.

## 7. Interface
Tela de revisão com uma questão por vez, recorte da questão, alternativas como botões, cartão inteiro em modal ou painel lateral 55/45 com PDF.js sem controles. Sem prints; conferências por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre | — | Aprovar §1–§3 e Onda 0 | Pendente |
| §4 item 2 — gate de produção e fila acumulada | Revogar gate L4 em favor do ADR-H1 e promover em blocos / manter | Promover em blocos começando pela segurança, UR1–UR4, PR #194 e **hotfix de leituras 500** | Pendente |
| Decisão 1 do docs/25: "anulada" conta como erro, sem pontos, e aparece como em branco nos relatórios | Registrar / alterar | Registrar; o código já grava "anulada" como código `N` sem alternativa (não pontua) | Pendente (§3) |
| R3 sem dados de decisões reais | Medir antes de ajustar / manter limiares | Medir na homologação antes de mudar limiares | Pendente |

## 9. Ramos desta entrega
Os hotfixes dos docs/152–157 foram ramos desta funcionalidade e terminaram com ela. O docs/153 ficou superado pela retirada do `_ctx` (FN-018).

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
