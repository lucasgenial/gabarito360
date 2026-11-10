# 047 — Qualidade, tentativas, resultado e documentos verificáveis (OMR1–3) — Planejamento

**Estado no tronco:** Em homologação
**Prioridade no plano mestre:** sem onda; está na fila de produção (§7, bloco "OMR1–3", PR #188–#190), aguardando validação e a decisão §4 item 2. O §5 é PROPOSTA.
**Natureza:** existente documentada a partir do código
**Origem:** OMR1 surgiu de urgência (foto incompleta aceita pelo detector); OMR2 e OMR3 foram ampliações pedidas pelo responsável — docs/158, 159, 160
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Recusar fotos que não permitem leitura confiável, preservar cada tentativa de correção sem sobrescrever a vigente, apresentar o resultado do aluno com a folha digitalizada e o histórico, e emitir PDFs cuja autenticidade qualquer pessoa pode conferir.

## 2. Escopo
- **Inclui:** barreira de qualidade (4 marcadores, geometria, nitidez, resolução) na câmera, galeria, upload e worker (OMR1-A); tentativas numeradas, reenvio sem sobrescrita, aplicar ou descartar proposta, histórico imutável com foto arquivada e hash (OMR1-B, OMR2-C); resultado com identificação em 3 linhas, barra de ações e menu Relatório, folha em PDF.js (OMR2-A/B); itens coloridos com leitura anterior e intervenção (OMR3-A/B); percentuais por questão por turma e geral para educadores (OMR3-C); PDF do aluno e PDF pedagógico com rodapé QR e numeração contínua (OMR3-D); registro imutável de emissão e validação pública por SHA-256 + HMAC (OMR3-E).
- **Não inclui:**
  - Leitura, ambíguos e fechamento → FN-045. Revisão questão a questão → FN-046.
  - Breadcrumb central e Solid Toast nas demais telas, carregamento regional geral → FN-076 (nasceram aqui em OMR2-C2).
  - Cabeçalhos institucionais dos PDFs → FN-071 (H1-D).
  - Selo dos documentos (R1 do docs/28) → FN-069.
  - Relatórios de prova, turma e consolidados → FN-049.

## 3. Fontes de verdade
- Mockups: `mockups/resultado.html`, `mockups/resultado-dinamico.html` ([docs/01](../../01-inventario-dos-mockups.md) seção 20). Modelo 2 de apresentação descartado (branch `design/resultado-modelo2`).
- Documentos de origem: [docs/158](../../158-omr1-qualidade-e-tentativas.md), [docs/159](../../159-omr2-resultado-e-historico-integrado.md), [docs/160](../../160-omr3-leituras-relatorios-validacao.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-005 e UC-010 do [docs/07](../../07-casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) §4.5 e §4.6.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-045 omr-correcao | Cartões, leitura e nota | Concluída |
| FN-046 revisao-leituras | Leitor PDF lateral (docs/157) e fotos | Parcial |
| FN-049 relatorios-resultados | Resultado individual (MP-022) e PDF | Concluída |
| FN-018 seguranca-urls-sessao | Fotos e PDFs por fetch autenticado | Em homologação |

**Funcionalidades que dependem desta:** FN-071 (cabeçalhos dos PDFs, H1-D), FN-076 (breadcrumb e carregamento progressivo), FN-053 e FN-050 (PDF da turma usa `EmissaoDocumento`), FN-069 (selo), FN-049 (PDFs com emissão registrada).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (OMR1-A) Qualidade obrigatória | Barreira em todos os canais e no worker | Foto ruim recusada com motivo | Entregue (homologação) | PR #188 |
| 1.2 (OMR1-B) Tentativas e histórico imutável | Reenvio sem sobrescrita | Proposta aplicada ou descartada com motivo | Entregue (homologação) | PR #188 |
| 1.3 (OMR1-C, OMR1-D) Folha digitalizada; ensaios | Absorvido por OMR2-B | — | Entregue (homologação) | PR #188, #189 |
| 2.1 (OMR2-A/B/C, C2, extras) Resultado, PDF.js, histórico paginado, breadcrumb, % do acompanhamento | Tela de resultado integrada | Histórico abaixo dos cards, paginado | Entregue (homologação) | PR #189 |
| 3.1 (OMR3-A/B) Fotos regionais, itens coloridos | Modelo 1 | Leitura anterior e intervenção visíveis | Entregue (homologação) | PR #190 |
| 3.2 (OMR3-C) Percentuais por questão | Turma e geral para educadores | Aluno não recebe percentuais | Entregue (homologação) | PR #190 |
| 3.3 (OMR3-D/E) PDFs e validação pública | PDF aluno/pedagógico; `/validar-documento` | Código e hash conferíveis | Entregue (homologação) | PR #190; homologação 45f9248 |
| 3.4 (OMR3-F/G) Testes e homologação | — | Publicado na homologação | Entregue (homologação) | docs/164 (45f9248) |
| 4.1 Validação do responsável e produção | — | Decisão expressa | Pendente | §3, §7 |

## 6. Contrato técnico (proporcional ao risco)
- **API**: `GET v1/provas/{id}/tentativas` (25 por página, filtro `aluno_id`), `GET v1/tentativas/{id}`, `GET v1/tentativas/{id}/imagem`, `POST v1/tentativas/{id}/aplicar|descartar`; `GET v1/alunos/{id}/resultados/{provaId}` e `/pdf` (`modelo=aluno|professor`); `GET v1/provas/{id}/resultados/pdf` (lote); `POST v1/documentos/validar` (público, 20/min).
- **Web**: `TentativaCartaoController` (`correcao.tentativas`, `correcao.tentativa*`), `ResultadoController` (`resultados.show`, `resultados.historico`, `resultados.pdf`, `resultados.lote`), `ValidacaoDocumentoController` (`/validar-documento`, público, 20/min).
- **Dados**: `cartoes.qualidade`, `cartao_tentativas`, `cartao_tentativa_eventos` com instantâneo cifrado (`2026_11_05_000001_preservar_tentativas_cartoes`); `documento_emissoes` com `codigo_hash`, `arquivo_sha256` e selo HMAC (`2026_11_05_000002_registrar_emissoes_documentos`).
- **Serviços**: `QualidadeCartao` (`omr/qualidade.py`, versão `omr-qualidade-1`), `HistoricoCartao`, `QuadroLeituras`, `EstatisticasQuestoes`, `EmissaoDocumento`, `omr/gerar_resultado.py`.
- **Integridade**: foto arquivada conferida por SHA-256 antes de servir ou aplicar; selo calculado com a chave da aplicação e as chaves anteriores; código `G360-` + 32 hexadecimais.
- **Revogação**: os PDFs revalidam versão de acesso e permissão depois da geração; o lote revalida todos os alunos antes de liberar o arquivo.

## 7. Interface
Tela de resultado (cards de igual altura, folha em PDF.js sem controles, histórico paginado abaixo), tentativas, validação pública. Breadcrumb e toast só nas telas de prova, correção e resultado (resto em FN-076). Sem prints; conferências por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre | — | Aprovar §1–§3 e Onda 0 | Pendente |
| §4 item 2 — gate de produção e fila acumulada | Promover em blocos / manter só na homologação | Promover em blocos depois do bloco de segurança | Pendente |
| Validação do responsável na homologação (§3) | Validar / pedir ajustes | Validar com roteiro por texto | Pendente |
| Rastreabilidade: as siglas OMR1–3 não aparecem nas mensagens de commit, só nos nomes das branches | Aceitar / registrar mapa PR ↔ subfase | Manter o mapa desta tabela como referência | — |

## 9. Ramos desta entrega
OMR2-C2 (breadcrumb e Solid Toast) e o carregamento progressivo nasceram aqui e seguem em FN-076.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
