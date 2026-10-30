# 23 — Plano de layout e design responsivo (do celular ao 4K)

> **Status:** proposta para aprovação (30/10/2026). Nada deste documento foi implementado.
> Segue a regra do projeto: planejar em passos grandes antes de modificar.
> Base para o app Android: o que for padronizado aqui vira a referência visual do app.

## 1. Objetivo

Um único padrão visual e de layout para **todas as telas e perfis**, que:

- aproveite **telas grandes** (1920, 2560 e 3840 px) sem virar uma faixa estreita no meio da tela;
- funcione no **celular e no tablet** sem rolagem lateral nem conteúdo cortado;
- tenha **menu estável** (sem itens quebrando em duas linhas);
- use os **mesmos componentes** (cabeçalho de página, cartões, indicadores, tabelas, formulários, botões, avisos) em todas as telas;
- não mostre **dados fixos ou falsos**.

## 2. Como a auditoria foi feita

- **43 telas** renderizadas com os **dados reais** de produção (só leitura), pelos perfis admin, diretor de núcleo,
  diretor escolar, coordenador, professor, aluno e visitante.
- Cada tela medida em **6 larguras**: 375 (celular), 768 (tablet), 1280 (notebook), 1920 (Full HD),
  2560 (4K com escala de 150% do Windows) e 3840 px (4K com escala de 100%) — **258 medições**:
  rolagem lateral, altura do menu, largura útil do conteúdo, textos menores que 12 px e conteúdo cortado.
- Capturas em tamanho real com o Chrome; as principais estão em `docs/evidencias/layout-2026-10-30/`.
- Levantamento do código das 60 telas (CSS, cores, tamanhos de fonte, componentes repetidos).

## 3. O que foi encontrado

### A. Telas grandes
| Largura da tela | Largura do conteúdo | Ocupação |
|---|---|---|
| 1280 px | 1267 px | 99% |
| 1920 px | 1280 px | **67%** |
| 2560 px | 1280 px | **50%** |
| 3840 px | 1280 px | **33%** |

- O conteúdo tem largura máxima fixa de **1280 px** (`--container`). Em 4K sobra uma faixa vazia de cada lado
  (`admin-painel_3840.png`) e, com escala 100%, a letra base de 16 px e os textos de 12–13 px ficam minúsculos.
- Login em 4K: o formulário fica pequeno no centro de meia tela vazia e o texto de apresentação some no canto
  (`visitante-login_3840.png`).
- Quadros grandes (ranking alunos × provas, relatórios) ficariam melhores com mais largura em telas grandes.

### B. Menu
- **Quebra em duas linhas** nos painéis do diretor e do coordenador **de 1280 px até 4K**: o selo com o nome da
  escola (no topo, à direita) é longo e espreme o menu; "📷 Corrigir", "📈 Relatórios" e "Mais ▾" quebram
  (`diretor-painel_1280.png`).
- Itens com **emoji** (📷 📈) no topo, enquanto a barra do celular usa ícones desenhados — dois estilos.
- Entre 1024 e ~1300 px o menu do topo (6 itens + selo + tema + avatar) fica no limite.
- A faixa **"🇧🇷 Governo Federal"** aparece em todas as páginas, mas a rede é estadual/municipal e o sistema
  também atende escolas privadas.

### C. Celular e tablet
- **Gabarito oficial** (`/provas/{id}/gabarito`): rolagem lateral de 68 px — a página inteira desliza
  (`diretor-provas-gabarito-show_375.png`).
- **Acompanhamento da correção por turma** e **lista de turmas**: conteúdo cortado dentro de cartões.
- As demais 41 telas não têm rolagem lateral (resultado da etapa 5), mas cada uma se reorganiza em larguras
  diferentes (ver D).

### D. Inconsistências no código (causa da falta de padrão)
| Item | Hoje |
|---|---|
| Telas com bloco `<style>` próprio | **48 de 60** (1.503 linhas de CSS espalhadas) |
| Atributos `style="…"` nas telas | **933** |
| Pontos de quebra (breakpoints) diferentes | **19** (480, 500, 540, 560, 600, 640, 680, 700, 720, 768, 850, 860, 900, 960, 980, 1024, 1080, 1100 px) |
| Cores fixas (hex) nas telas | **83** distintas — o tema escuro não as acompanha |
| Tamanhos de fonte diferentes | **44** |
| Componentes redefinidos em cada tela | indicadores (`kpi-grid`) em 12 telas, `kpi-strip` em 6, títulos de seção em 6, botões em 6, modal em 4 |
| Layouts | 3 (`app`, `auth`, `public`) com regras próprias |

### E. Conteúdo fixo ou falso
- Painel do admin: **"Rede Municipal"** (a rede é estadual) em 3 lugares; **"aplicações em October"** — o sistema
  está com idioma inglês (`APP_LOCALE=en`), o que também afeta nomes de mês e mensagens padrão.
- Painel do diretor: **"Ensino Fundamental · Turnos manhã e tarde"** fixo (a escola tem os dados reais do Censo).
- Login: estatísticas fixas (**"12 s", "98,6%", "340 escolas"**), **"Acesso protegido por autenticação gov.br"**
  (não existe login gov.br) e aba **"Cadastrar"**.
- Página inicial: "+340 escolas", "98,6%".
- Números como **"1 , 8"** com espaços: a fonte monoespaçada usada nos indicadores separa a vírgula.

## 4. Padrão proposto

### 4.1 Régua única de larguras (substitui os 19 breakpoints)
| Nome | Largura | Uso |
|---|---|---|
| `xs` | < 480 | celular pequeno |
| `sm` | 480–767 | celular |
| `md` | 768–1023 | tablet |
| `lg` | 1024–1439 | notebook |
| `xl` | 1440–1919 | monitor |
| `2xl` | ≥ 1920 | Full HD, 2K, 4K |

### 4.2 Largura do conteúdo (fluida, com limites por tipo de tela)
- **Padrão** (painéis, detalhes): ocupa a tela com margens laterais proporcionais, até **1600 px**.
- **Telas de trabalho** (listas, quadros, relatórios, correção): até **1920 px**.
- **Formulários e leitura**: coluna de no máximo ~960 px, centralizada, com o resumo ao lado quando couber.

### 4.3 Tipografia e espaçamento que crescem com a tela
- Medidas em `rem` e **escala automática**: letra base de 15–16 px no celular/notebook, subindo suavemente até
  **18 px em telas ≥ 1920** (e espaçamentos juntos), para que o 4K com escala 100% não fique minúsculo.
- Escala fixa de tamanhos (ex.: 12, 14, 16, 18, 22, 28, 36) no lugar dos 44 atuais.
- Números dos indicadores na fonte do texto (sem "1 , 8").

### 4.4 Navegação
- **Celular e tablet (< 1024):** barra inferior (já existe) + "Mais".
- **Notebook e telas grandes (≥ 1024):** **menu lateral** à esquerda, com ícone + nome, recolhível para só
  ícones; o topo fica com logo, escola/rede em uso (com reticências quando longo), tema e avatar.
  Aproveita a largura das telas grandes e acaba com a quebra do menu do topo.
  *(Alternativa: manter o menu no topo, compacto, com o selo da escola fora dele — ver decisão 1.)*
- Ícones desenhados (os mesmos da barra do celular) em todos os itens; sem emojis.
- Faixa superior: trocar "Governo Federal" pela identificação do **ente da rede** (com o logo já cadastrado) ou remover.

### 4.5 Componentes únicos
Tudo no `gov.css` + componentes Blade, e as telas passam a só montar:
`<x-pagina>` (cabeçalho: título, subtítulo, ações), `<x-indicadores>`/`<x-indicador>` (KPIs que se ajustam:
1 coluna no celular, 2 no tablet, 4 no notebook, até 6 em telas grandes), `<x-cartao>`, `<x-secao>`,
tabela `lista-cel` e `quadro-cel` (etapa 5), `<x-formulario>`/`<x-campo>`, `<x-aviso>` (sucesso/erro/info),
`<x-vazio>` (estado vazio), abas, botões e selos.
- Grades de painel com colunas automáticas: em telas grandes os blocos se distribuem em 3–4 colunas em vez de 2.
- **Só cores por variável** (tokens): o tema escuro passa a funcionar em todas as telas.
- Modais restantes viram páginas ou painéis laterais (melhor no celular).

### 4.6 Conteúdo
- Idioma **pt-BR** (`APP_LOCALE=pt_BR`, datas e meses em português).
- Remover textos fixos/falsos (seção 3.E): rede, oferta e turnos vêm do cadastro; login sem estatísticas
  inventadas, sem "gov.br" e sem a aba "Cadastrar" (cadastro é feito por quem gerencia).

## 5. Etapas (passos grandes, um PR cada, com capturas antes/depois)

| Etapa | O que | Aceite |
|---|---|---|
| **L0 — Correções urgentes** | Menu quebrando (selo da escola), rolagem do gabarito no celular, conteúdo cortado (correção por turma, turmas), idioma pt-BR, textos fixos/falsos | Nenhuma quebra de menu em 1024–3840; 0 rolagem lateral em 375; nenhum dado fixo |
| **L1 — Fundação** | Régua de larguras, container fluido, tipografia em `rem` com escala, tokens de cor/espaço/fonte, componentes Blade base, ícones | Componentes documentados numa página interna de referência ("vitrine") |
| **L2 — Navegação** | Menu lateral (ou topo compacto, conforme decisão 1), topo novo, faixa do ente, barra do celular mantida | Mesmo menu em todos os perfis e larguras |
| **L3 — Painéis** | Os 8 painéis por perfil com os componentes novos | Painéis ocupam ≥ 80% da tela em 1920–3840 sem esticar texto |
| **L4 — Listas e detalhes** | Escolas, turmas, alunos, provas, equipe e as páginas de detalhe | Sem `<style>` próprio nessas telas |
| **L5 — Formulários** | Membro, escola, aluno, prova, gabarito, permissões | Mesmo formulário em todas; uma coluna no celular |
| **L6 — Correção e relatórios** | Correção (incl. câmera), resultado do aluno, relatórios de prova/turma/escola/núcleo/rede; impressão A4 preservada | Quadros usam a largura das telas grandes; PDF igual ao atual |
| **L7 — Páginas públicas e login** | Início, planos, login, recuperação de senha | Layout correto em 375–3840 |
| **L8 — Verificação contínua** | O script desta auditoria (43 telas × 6 larguras) vira checagem antes de cada PR | Relatório sem rolagem lateral, sem menu quebrado, ocupação mínima |

Ordem sugerida: L0 → L1 → L2 → L3 → L4 → L5 → L6 → L7, com L8 a partir de L1.
A **L0 pode ir para produção já**, independente das decisões abaixo.

## 6. Decisões que preciso de você

1. **Menu no computador:** **lateral** (recomendado: aproveita telas grandes e não quebra) **ou** topo compacto?
2. **Largura máxima:** 1600 px no padrão e 1920 px em listas/relatórios — ou ocupar a tela toda em qualquer largura?
3. **Faixa superior "Governo Federal":** trocar pela identificação do ente da rede (logo + nome) ou remover?
4. **Login:** remover as estatísticas, a frase "gov.br" e a aba "Cadastrar"? (O "Cadastrar" hoje não faz sentido:
   quem cadastra usuários é o gestor.)
5. **Página inicial pública:** os números "+340 escolas" e "98,6%" são de marketing — manter, trocar por dados reais ou retirar?
6. **Identidade visual:** manter o padrão gov.br (azul institucional, fonte Rawline/Raleway) ou você tem uma
   identidade própria do Gabarito360 (cores, logo) para aplicar agora?

## 7. Evidências

`docs/evidencias/layout-2026-10-30/`:
- `diretor-painel_1280.png` — menu quebrando em duas linhas por causa do selo da escola;
- `admin-painel_3840.png` — 4K: conteúdo em 1/3 da tela, texto pequeno, "Rede Municipal", "October", "1 , 8";
- `visitante-login_3840.png` — login em 4K;
- `diretor-provas-gabarito-show_375.png` — gabarito com rolagem lateral no celular;
- `diretor-membros-create_1920.png` — formulário em Full HD (referência de boa leitura, mas largura fixa).
