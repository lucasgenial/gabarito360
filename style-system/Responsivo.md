# Responsivo

O app é responsivo (ADR-0057). Toda tela nova ou alterada, e todo mockup, atende a todas as faixas abaixo, nos temas claro e escuro. Os cortes vêm do CSS e do JS de `apps/web/public/` (commit `37ba645`). `EST` = `resources/css/estrutura-app.css`; `LEG` = `public/css/app-conteudo-legado.css`; `TAB` = `public/css/tabelas.css`.

## Faixas

| Faixa | Largura | Casca | Tabelas | Toque |
|---|---|---|---|---|
| Celular pequeno | até 400 px | Casca de celular: navegação inferior e folha Mais; lateral e rodapé de versão ocultos | Cards | 44 px |
| Celular | 401 a 720 px | Casca de celular | Cards | 44 px |
| Celular largo e tablet pequeno | 721 a 767 px | Ainda casca de celular | Tabela, com rolagem dentro do card | 44 px |
| Tablet retrato | 768 a 1023 px | Lateral em fluxo, compacta (80 px) por padrão; padding lateral do conteúdo 16 px; rodapé de versão visível | Tabela | 44 px em `pointer:coarse` |
| Tablet paisagem e notebook | 1024 a 1279 px | Lateral compacta por padrão; padding lateral 32 px; edição de painéis a partir de 1024 px de largura e 700 px de altura; painel lateral de documento a partir de 1100 px | Tabela; ações quebram até o contêiner ter 64rem | idem |
| Notebook e desktop | 1280 a 1919 px | Lateral expandida (256 px) por padrão; tela em toda a área útil (ADR-0021) | Tabela | idem |
| Telas grandes | 1920 a 3840 px | Igual ao desktop. A área logada não tem escala: `.tw2-app` fixa `--escala: 1` e `zoom: 1` (`EST:6`). As grades crescem pela área útil. | Tabela | idem |

"Por padrão" significa sem preferência gravada. Quem clica em recolher grava `g360-menu`, e a escolha vale em qualquer largura a partir de 768 px (`layout-preferencias.js:3-5`).

**Lacuna 4K, para decisão do responsável.** A área logada não tem regra própria acima de 1920 px. A escala 1,125 / 1,25 / 1,5 (a partir de 1920 / 2560 / 3200 px, `LEG:248-252`) vale só nas páginas públicas e de acesso (`.area-publica`). O ADR-0057 registra a lacuna. Ninguém decidiu ainda o que cresce na área logada em 2560 e 3840 px.

## Cortes canônicos

Use só estes cortes em tela nova.

| Corte | O que muda | Fonte |
|---|---|---|
| 400 / 401 | Revisão: gap das alternativas cai para 4; lista de cartões em 1 coluna | `LEG:636`; `leituras-resultados.css:16` |
| 720 / 721 | `x-tabela` (modos lista e seleção) vira cards até 720 px | `TAB:11,53,66`; `LEG:231,932`; `EST:147` |
| 767 / 768 | Casca de celular até 767 px; lateral, recolher e rodapé de versão a partir de 768 px; a folha Mais fecha ao passar de 768 px | `EST:71,75-100`; `layout-interacoes.js:5,22` |
| 1023 / 1024 | Até 1023 px: barra de salvar sobe, formulários em 1 coluna, dica de teclado da revisão oculta. A partir de 1024 px: edição de painéis | `LEG:225,424,554,661`; `painel-grade.js:3`; `editor-paineis.js:3` |
| 1099 / 1100 | Painel lateral de documento (revisão, resultado, cartão) a partir de 1100 px; abaixo, botão Visualizar e modal | `LEG:597-599,618-619`; `correcao-revisao.js:67`; `painel-cartao.js:3` |
| 1279 / 1280 | Lateral expandida por padrão a partir de 1280 px | `layout-preferencias.js:4` |
| Altura 699 / 700 | Até 699 px de altura (com largura até 1023): mover grupo, organizar painel e mover widget ficam ocultos | `painel-grade.css:342,389` |

Cortes avulsos que existem no CSS e não são modelo para tela nova: 359, 360, 380, 440, 479, 480, 500, 600, 640, 700, 800, 860, 899 e 1500 px. Eles ficam como divergência a consolidar (ADR-0057).

## Regras por ponteiro

| Consulta | Efeito | Fonte |
|---|---|---|
| `pointer:coarse` | Controles do cabeçalho e itens do menu da conta com 44 × 44 px | `EST:73` |
| `pointer:coarse` ou até 767 px | Em Turmas, `.btn`, `.input`, `.select` e ações de tabela com 44 px; `.tabela-ordenar` com 44 px em todas as telas | `EST:127,139`; `TAB:101` |
| `pointer:coarse` | Botão mover do painel com 44 × 44 px | `painel-grade.css:341` |
| `any-pointer:coarse` (JS) | Ajustes do GridStack no painel e no editor | `painel-grade.js:1`; `editor-paineis.js:1` |

Regra: em tela de toque, todo alvo tem pelo menos 44 × 44 px (token `alvo-toque`). O botão SMALL (`pq`, 32 px) mantém o alvo de 44 px.

## Consultas de contêiner

| Contêiner | Definição | Corte | Efeito | Fonte |
|---|---|---|---|---|
| `pagina` | `.g-pagina` (`container: pagina / inline-size`) | menor que 36rem (576 px) | Oculta `.acao-turma-contexto` (texto repetido da ação) | `EST:61,137` |
| `pagina` | idem | menor que 24rem (384 px) | Oculta `.acao-turma-editar`; `.tu1r3-acao-editar` vira ícone de 44 px | `EST:138` |
| `tabela` | `.tabela-area` (`container: tabela / inline-size`) | 64rem (1024 px) ou mais | Ações em linha única, `width: max-content` | `TAB:36,90` |
| anônimo | contêiner do widget de painel | até 420 px | Cabeçalho do widget quebra em linhas | `painel-grade.css:387` |

Use consulta de contêiner quando o componente depende do espaço que recebe, não só da janela.

## Altura

- Edição de painéis: só com 1024 px de largura e 700 px de altura, ou mais (ADR-0040).
- Não há outra regra de altura. Celular em paisagem (por exemplo 640 × 360) não tem regra própria.

## Movimento e impressão

- `prefers-reduced-motion: reduce` desliga transições da casca e animações do carregamento, da marca, dos toasts, do estado de tela e dos gráficos.
- `@media print` esconde a casca e imprime em A4.
- Não há regra de `orientation` nem de `hover`. Os estados `:hover` valem também em toque.

## Larguras de referência

Confira cada tela e cada mockup nestas larguras, nos temas claro e escuro. Registre o resultado por texto (ADR-0049).

| Largura | Faixa | O que conferir |
|---|---|---|
| 320 | Celular pequeno | Sem rolagem horizontal; ações de `x-pagina` só com ícone em contêiner abaixo de 24rem; cards de tabela legíveis; navegação inferior com 5 itens; toasts com largura total |
| 360 | Celular pequeno | Folha Mais; botões de 44 px; formulários em 1 coluna; modal de imagem |
| 390 | Celular | Revisão com 5 alternativas; marca com o nome no cabeçalho |
| 720 | Celular | Último pixel da tabela em cards |
| 768 | Tablet retrato | Lateral compacta aparece; navegação inferior some; rodapé de versão aparece; tabela volta a ser tabela; padding lateral 16 |
| 1024 | Tablet paisagem | Edição de painéis liberada (com altura 700 ou mais); formulários de 2 colunas; padding lateral 32 |
| 1280 | Notebook | Lateral expandida por padrão; painel lateral de documento ao lado do conteúdo |
| 1440 | Desktop | Área útil sem faixa vazia; tabela sem rolagem interna |
| 1920 | Telas grandes | Texto sem esticar; área logada sem escala |
| 2560 | Telas grandes | Área logada sem escala; grades ocupam a área útil |
| 3840 | 4K | Área logada sem escala: confira se nada fica pequeno demais (lacuna 4K) |

Larguras complementares, úteis por causa dos cortes: 721 e 767 (casca de celular com tabela), 1099 e 1100 (painel de documento), 1279 (lateral compacta), e altura 699 (edição de painéis).

## O que muda na casca

| Item | até 767 | 768 a 1023 | 1024 a 1279 | 1280 ou mais |
|---|---|---|---|---|
| Lateral | oculta | 80 px (padrão) ou 256 | 80 (padrão) ou 256 | 256 (padrão) ou 80 |
| Botão recolher | oculto | visível | visível | visível |
| Controles do cabeçalho | 44 × 44 | 32 (44 em toque) | idem | idem |
| Navegação inferior | visível, itens de 56 px | oculta | oculta | oculta |
| Rodapé de versão | oculto (ADR-0044) | visível | visível | visível |
| Padding do conteúdo | 20 / 16 / 92 + safe-area | 20 / 16 / 84 | 20 / 32 / 84 | 20 / 32 / 84 |
| Meu espaço | topo, 1 coluna | topo, 2 colunas; fixo na lateral expandida | idem | idem |
| Toasts | largura total até 640 | 360 px | 360 px | 360 px |

## Lacunas registradas

1. 721 a 767 px: casca de celular com tabela em modo tabela.
2. Telas grandes: a área logada não escala e não tem regra própria para 4K. Decisão do responsável.
3. Sem regra de orientação: tablet em retrato e em paisagem só diferem pela largura.
4. Sem `@media (hover)`.
5. Só o painel tem regra de altura.
6. Botões legados não garantem 44 px em toque fora de listas definidas.
7. Avatar de entidade de 64 / 56 px (ADR-0032) sem classe no CSS.
8. 1440 a 1919 px sem regra própria, exceto o corte 1500 de uma tela.
9. Filtros em modal só em Turmas.
10. Cortes avulsos fora das faixas canônicas.
11. Só a folha Mais usa folha inferior; modais e filtros ficam centrados também no celular.
12. A casca logada usa só `safe-area-inset-bottom`.
