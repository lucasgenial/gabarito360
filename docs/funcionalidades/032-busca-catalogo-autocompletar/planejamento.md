# 032 — Busca do catálogo por autocompletar — Planejamento

**Estado no tronco:** Adiada
**Prioridade no plano mestre:** Futuro (candidatas sem prioridade, §5) — adiada até haver uso real
**Natureza:** evolução de existente (FN-031)
**Origem:** surgiu na entrega de desempenho F5 em 09/11/2026 — docs/200 (F5, PERF-09/10/12)
**Atualizado em:** 2026-11-09, por lucasgenial

> Planejamento enxuto de funcionalidade adiada. Nada aqui está aprovado para execução.

## 1. Objetivo
Trocar a busca nacional por texto do catálogo — hoje `LIKE '%termo%'` sobre ~207 mil entradas, perto de 1,8 s por requisição na homologação — por um componente único de autocompletar, rápido e acessível, usado em todos os seletores de instituição.

**Motivo do adiamento (decisão do responsável, 09/11/2026):** a busca é usada em seletores pontuais, não em navegação contínua, e a aplicação ainda não tem uso real. O buffer pool de 1 GB (F6-1) segue aplicado na homologação.

## 2. Escopo preliminar
- **Inclui (desenho preferido do docs/200, a validar):**
  - um componente único de autocompletar acessível;
  - busca por prefixo de palavra; código INEP continua como busca exata;
  - filtro estruturado (UF/município) antes do texto;
  - só os 10 primeiros resultados, sem contagem exata;
  - no navegador: mínimo de 3 letras (2 com UF), espera curta e descarte de respostas antigas;
  - índice e facetas reconstruídos a cada edição publicada; resultados em cache por edição;
  - municípios servidos como lista por UF.
- **Não inclui:**
  - Conteúdo, importação e manutenção do catálogo → FN-031.
  - Decidir se o cadastro de escola troca o Censo pelo catálogo → FN-033.
  - Ajustes de infraestrutura (buffer pool, FPM) → FN-077.
  - Fixar a ordem de junção mantendo a semântica atual (ganho estimado de ~40%, proposto em F5) → não executado; reavaliar ao retomar.

## 3. Pontos de uso registrados (docs/200)
| # | Ponto de uso | Onde está hoje | Base |
|---|---|---|---|
| 1 | Professor autônomo declarando onde atua ("Minhas instituições") | `InstituicoesPessoaisController` → `GET /api/v1/catalogo/instituicoes` | catálogo publicado (~217 mil entradas) |
| 2 | Instituição cadastrando escolas | `escolas/censo/buscar` e `escolas/censo/pesquisar` → `GET /api/v1/censo/escolas[/{inep}]` (`escola.criar`) | `censo_escolas` (19.554 escolas) — outra base |
| 3 | Pedido de inclusão ("Minhas solicitações") | `CatalogoAtendimentoController` → `/api/v1/catalogo/minhas-solicitacoes` | catálogo publicado |
| 4 | Atendimento e gestão internos | `/app/catalogo/atendimento/buscar`, `/app/catalogo/gestao` | catálogo publicado |
| 5 | Seleção de UF e município nesses fluxos | `GET /api/v1/catalogo/{ufs,municipios}`; rotas web `*/municipios` | 5.503 municípios |

## 4. Fontes de verdade
[docs/200](../../200-f5-f6-diagnostico-catalogo.md) (medições, decisão e desenho); [FN-031](../031-catalogo-instituicoes/planejamento.md); [casos-de-uso.md](casos-de-uso.md); [docs/03](../../03-perfis-e-permissoes.md) (sem permissões novas). Sem mockup: exige UI/UX.

## 5. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-031 catalogo-instituicoes | Dados, edições e rotas atuais | Em homologação |
| FN-077 desempenho | Medição com 1000 usuários simultâneos; diagnóstico F5/F6 | Parcial |
| FN-075 tailwind-ui / FN-076 carregamento-estados | Componente único e estados de carga/erro | Parcial |
| Uso real da aplicação | Condição de retomada | Não atendida |

**Funcionalidades que dependem desta:** nenhuma bloqueada. FN-027 (PA1-A), FN-029 e FN-033 passariam a usar o componente se existir; não devem criar seletores paralelos.

## 6. Fases (preliminares)
| Subfase | Entrega | Status |
|---|---|---|
| 1.1 Semântica da busca | Decidir prefixo, palavras e obrigatoriedade de UF | Adiada |
| 1.2 Índice e cache por edição | Estrutura reconstruída na publicação | Adiada |
| 1.3 Endpoint de sugestão | Top 10 sem contagem | Adiada |
| 1.4 Componente único | Substituir os seletores dos pontos de uso | Adiada |
| 1.5 Medição | Padrão de 1000 usuários simultâneos | Adiada |

## 7. Medições de partida (homologação, docs/200)
| Filtros | Antes | Após F6-1 |
|---|---|---|
| Texto "escola municipal", sem UF (23.300 resultados) | 3,48 s | 1,79 s |
| Texto de 4 letras, sem UF | 3,25 s | 1,70 s |
| Texto sem resultado, sem UF | 1,46 s | 0,80 s |
| Texto + UF / só UF | 0,13 / 0,14 s | 0,13 / 0,14 s |

Nenhuma dessas medições usou carga simultânea. Hipóteses de índice de trigramas foram exploradas em schema descartável, sem carga.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Retomada | Quando houver uso real | Manter adiada | Decidida em 09/11/2026 (adiar) |
| Semântica: substring → prefixo/palavras, ou UF obrigatória para texto curto | Muda o resultado da busca | Decisão de produto; não alterar silenciosamente | Pendente |
| §4 item 8 — identidades para a carga de 1000 usuários simultâneos | Contas sintéticas só na VM 120 | A do plano mestre | Pendente |
| Cadastro de escola usar o catálogo em vez do Censo (ponto 2) | Manter Censo / migrar | Avaliar em FN-033 | Pendente |
| Uma busca nacional ocupa um dos dois processos FPM da API por segundos | Aceitar enquanto sem uso / limitar | Reavaliar antes de qualquer abertura comercial | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
