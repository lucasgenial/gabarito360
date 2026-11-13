# Interface e design system

**Atualizado em:** 13/11/2026, por lucasgenial

Este documento descreve a interface do Gabarito360 como ela existe na branch atual: fontes de verdade visuais, identidade, Tailwind, layout, tabelas, carregamento, gráficos, acessibilidade e inventário das telas por perfil. Os painéis de turmas e o editor têm documento próprio: [Painéis e editor](10-paineis-e-editor.md).

Funcionalidades relacionadas: FN-052 (painéis por perfil), FN-066 (avatares), FN-070 (identidade visual e gráficos), FN-071 (marca e cabeçalhos), FN-072 (site público), FN-073 (layout responsivo), FN-074 (tabelas), FN-075 (Tailwind e UX transversal) e FN-076 (carregamento e estados de erro). O catálogo está em [funcionalidades](../funcionalidades/README.md).

## 1. Fontes de verdade visuais

Em conflito, vale a ordem abaixo ([ADR-0056](../decisoes/0056-app-atual-como-referencia-visual.md), desde 11/11/2026):

1. Mockup novo aprovado pelo responsável, feito sobre a casca, os componentes e o CSS reais de `apps/web` (exemplo: `mockups/leitor360.html`, FN-079).
2. App atual: views Blade, componentes e CSS de `apps/web` na `main`, que é o código publicado na homologação.
3. Documentação oficial em `docs/`.
4. Design system do app em `style-system/`: tokens nos dois temas, tipografia, espaçamento, componentes com prévia que carrega o CSS real, faixas por aparelho e divergências. Ele espelha o artifact "Gabarito360 — Design System" no claude.ai e deriva do item 2.
5. `mockups/` originais e `style-system/historico/`: **históricos**. Servem para consultar o escopo e os textos de uma tela. Não decidem o visual. O manifesto antigo é `mockups/DESIGN-MANIFEST.json`.
6. Regras do legado (somente regra de negócio).

Regras:

- A pasta `mockups/` e a pasta `style-system/` guardam desenho de referência. Elas não são código de produção.
- Toda tela nova precisa de origem rastreável: mockup novo aprovado, ou aprovação posterior documentada.
- Um mockup novo carrega o CSS de `apps/web/public/css/` por caminho relativo, usa a casca e os componentes reais e limita o CSS próprio ao que é novo, com os tokens existentes (ADR-0056).
- A pasta `previas/` e `apps/web/public/previas/` guardam páginas isoladas de aprovação (componentes Tailwind, editor de painéis). Elas usam dados fictícios e não chamam a API.
- Imagens versionadas só são aceitas como assets da aplicação (`apps/web/public/`, `apps/web/resources/images/`) ou como desenhos de design. O script `scripts/verificar_imagens.py` confere isso na CI. Nenhum print da aplicação é mantido.

### 1.1 O app é responsivo (regra)

Toda tela e todo mockup atendem a todas as faixas de aparelho ([ADR-0057](../decisoes/0057-app-responsivo-em-todas-as-faixas.md)):

| Faixa | Largura | Regras principais |
|---|---|---|
| Celular pequeno | 320 a 400 px | Casca de celular. Tabelas em cards. Ações de página viram ícone de 44 px em contêiner abaixo de 24rem. |
| Celular | 401 a 720 px | Navegação inferior e folha "Mais" (ADR-0032). Lateral e rodapé de versão ocultos (ADR-0044). Tabelas em cards. Alvos de 44 px. |
| Celular largo e tablet pequeno | 721 a 767 px | Casca ainda de celular. A tabela volta a ser tabela, com rolagem dentro do card. |
| Tablet retrato | 768 a 1023 px | Lateral compacta (80 px) por padrão, margem lateral de 16 px, rodapé de versão visível. Alvos de 44 px em telas de toque. |
| Tablet paisagem e notebook | 1024 a 1279 px | Lateral compacta por padrão. Edição de painéis disponível a partir de 1024 px de largura e 700 px de altura. Painel lateral de documento a partir de 1100 px. |
| Notebook e desktop | 1280 a 1919 px | Lateral expandida (256 px) por padrão. As telas ocupam toda a área útil (ADR-0021). |
| Telas grandes | 1920 a 3840 px (4K) | Área logada sem escala de interface (`.tw2-app` fixa a escala em 1); as grades crescem pela área útil. A escala 1,125, 1,25 e 1,5 vale só nas páginas públicas e de acesso. ⚠ Lacuna: a área logada não tem regra própria para 4K; a decisão fica com o responsável. |

Larguras de referência para conferir: 320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px, nos temas claro e escuro. Em telas de toque (`pointer: coarse`), os alvos têm 44 px. Componentes que dependem do espaço usam `@container`. Um corte novo exige registro no design system.

O inventário original tinha 30 telas mockadas. O Gabarito360 acrescentou telas sem mockup, sempre com aprovação posterior: planos, checkout, assinatura, painéis da secretaria e do aplicador, seleção de vínculo, cópias entre espaços, disciplinas, catálogo de instituições e outras. A tabela da seção 11 resume o inventário atual.

## 2. Identidade visual e tokens

### 2.1 Direção "Atual refinada"

A identidade é própria. Ela substituiu o padrão gov.br, herdado dos mockups, em 30/10/2026 (V1 a V5, concluídas).

| Item | Valor |
|---|---|
| Azul principal (`--accent`) | `#1d4f9e` (hover `#153b78`, fundo sutil `#dfe9f8`) |
| Verde-água de atenção (`--atencao`) | `#0b7f75` |
| Sucesso (`--success`) | `#168821` |
| Aviso (`--warn`) / erro (`--danger`) | `#ffcd07` / `#c62f1c` |
| Fundo (`--bg`) / superfície (`--surface`) | `#f4f6fa` / `#ffffff` |
| Série 1 a 5 dos gráficos | `#1d4f9e`, `#008a7c`, `#b7791f`, `#7b3fa0`, `#c2410c` |
| Fonte de texto | Figtree (páginas públicas e conteúdo legado) |
| Fonte de código | Roboto Mono |
| Raios | 4, 6 e 8 px; pílula 9999 px |
| Espaços | 4, 8, 12, 16, 20, 24, 32 e 48 px |

Os tokens vivem em `apps/web/public/css/app.css` (`:root`). O tema escuro redefine os mesmos tokens. O alternador de tema fica no cabeçalho.

Decisões de design registradas em 30/10/2026:

- Sem degradê azul decorativo nas páginas internas, sem emojis como ícones e sem frases de efeito.
- Ícones: uma família só, a G360 Icones, própria e entregue como fonte ([ADR-0063](../decisoes/0063-familia-de-icones-propria-em-fonte.md), 13/11/2026). Ela tem contorno e preenchido com os mesmos códigos, e a cor vem só de token. A especificação e o modo de adicionar um ícone estão no `style-system/` (seção Iconografia).
- Proibido usar emoji, caractere Unicode ou SVG avulso como ícone. Não há exceção: o Fechar do visor da câmera usa o ícone `fechar`.
- Um título por bloco. Nada de rótulo pequeno sobre outro título.
- Cartão só onde ele separa algo. Tabelas e listas ficam direto na página.
- Contraste mínimo de 4,5:1 para texto normal. O azul sobre branco tem 7,9:1.
- Números dos indicadores usam a fonte do texto, não a monoespaçada ("1 , 8" era o defeito anterior).
- Nenhum número ou selo inventado: sem "340 escolas", sem "gov.br", sem "Rede Municipal" fixo. Dados vêm do cadastro.
- Idioma da interface: pt-BR (`APP_LOCALE=pt_BR`).

### 2.2 Marca oficial (H1)

A marca oficial usa o símbolo (círculo com marca de verificação) e o nome em fonte de marca.

| Item | Valor |
|---|---|
| Azul-marinho / azul / branco | `#001534` / `#0588FC` / `#FFFFFF` |
| Nome | "Gabarito" e "360": Figtree 800; somente o "6" em Albert Sans 800 |
| Degradê dos painéis de acesso | `#1d4f9e` para `#001534`, 160 graus (`--g360-access-gradient`) |
| Componente | `<x-marca variant="horizontal\|icon" size="sm\|md\|lg" theme="auto\|light\|dark" :animated="false">` |
| Fonte do vetor | `apps/web/resources/brand/gabarito360-icon.svg` |
| Assets publicados | `apps/web/public/brand/` (SVG, PNG, PNG de e-mail, imagem 1200x630 de compartilhamento) |
| Fontes da marca | `apps/web/public/fonts/brand/` (WOFF2 e licenças OFL) e `apps/api/omr/fonts/` (TTF, para PDF) |
| Ícones de app | favicons, `apple-touch-icon`, `android-chrome-*`, `icon-*`, `maskable-icon-*`, `manifest.json` e `site.webmanifest` na raiz de `public/` |

Regras:

- Preserve o vetor oficial. Não desenhe um símbolo aproximado.
- A animação de confirmação é opcional, dura 1,05 s e não repete. `prefers-reduced-motion` a desativa.
- `layouts/_marca-head.blade.php` centraliza favicons, manifesto e metadados OG/Twitter. Os metadados nunca usam nome, nota, ID ou contexto de usuário.
- O gerador é `scripts/branding/gerar_assets.py` e o verificador é `scripts/branding/verificar_assets.py`. O inventário de hashes está em `resources/brand/assets-sha256.json`.
- Documentos em PDF e impressos usam o mesmo resolvedor de identidade. Escola com rede mostra a logo da rede à esquerda e a da escola à direita. Escola sem rede mostra só a sua logo. O modo autônomo mostra a marca completa Gabarito360. O título do documento fica centralizado.
- Não regenere PDFs já emitidos. O hash do documento verificável cobre o PDF completo.
- Os rodapés públicos listam os canais oficiais (YouTube, Instagram, Facebook e TikTok) no componente `rodape-publico`.

### 2.3 Páginas públicas (apresentação de pré-lançamento)

A home (`/`) e `/planos` usam `config/apresentacao.php` e o parcial `planos/_apresentacao`. Elas não consultam o catálogo comercial da API.

- Quatro públicos de atendimento: Professor autônomo, Escolas, Núcleos e redes, Secretarias. São categorias de comunicação, sem perfil ou acesso novo.
- Nenhum preço, desconto, periodicidade, limite comercial, compra ou gratuidade é exibido.
- Recursos ainda indisponíveis recebem o selo literal **Em breve** em cada ocorrência. São sete frentes: elaboração de provas, acervo inteligente GII, marcações na página da prova, formatos digitais e questões abertas, análises TCT/TRI, integridade e padrões de respostas, e adesão de escolas autônomas.
- Rótulos como "Previsto no planejamento" ou "Roadmap" nunca aparecem em público.
- A seção "O que as pesquisas dizem" mantém três métricas de fontes primárias (OCDE/TALIS 2024 Brasil, registro institucional sobre ZipGrade e artigo de Wisniewski, Zierer e Hattie, 2020). Elas não são medições do Gabarito360.
- Demonstrações usam dados fictícios. Nenhum dado, nota ou imagem real de aluno aparece.
- A home e a página de manutenção são exportadas como HTML estático por `scripts/operacao/exportar-home.php` e `scripts/operacao/publicar-home.sh`. O Nginx as serve sem PHP, API, sessão ou banco. O bundle de assets é imutável (`/_institucional/marca-{sha256}/...`), com lista fechada de arquivos, limite de 5 MiB por arquivo e troca atômica do ponteiro `current`. Falha na exportação preserva a versão anterior. Veja os procedimentos em [Operação](../operacao/README.md).
- O cartão de compartilhamento usa `og:*` e `twitter:card` genéricos, com URL canônica absoluta. A origem pública é informada pelo operador (nunca IP ou credencial).
- A página Fale conosco tem classe própria de altura mínima, para não gerar rolagem excedente no desktop.

## 3. Tailwind (TW1 a TW7)

Decisão do responsável (08/11/2026): adotar o Tailwind CSS como base visual efetiva. A paleta Gabarito360 e os ativos de marca ficam. Fontes, espaços, raios, sombras e composição seguem a escala do Tailwind. A estética do CSS anterior não é um requisito a reproduzir.

O Tailwind fornece utilitários, não componentes prontos. Os componentes compartilhados em Blade definem a composição.

| Etapa | Escopo | Estado |
|---|---|---|
| TW1 | Inventário, fundação e prévia isolada de componentes | Concluída; prévia aprovada pelo responsável |
| TW2 | Estrutura autenticada: cabeçalho, menu lateral, avatar da conta, rodapé, títulos, área útil | Concluída e publicada na homologação |
| TW3 | Ações, tabelas e cards (SMALL, coluna Ações à direita, ordenação nos cabeçalhos) | Parcial |
| TW4 | Formulários e diálogos | Pendente |
| TW5 | Painéis, mídia e ferramentas (GridStack, ECharts, agenda, recorte de imagem) | Pendente |
| TW6 | Autenticação, site público, erros, e-mails, documentos | Pendente |
| TW7 | Validação transversal por perfil, tema, zoom e teclado | Contínua |

Como o Tailwind está ligado hoje (confirmado no código):

- A folha da estrutura é `apps/web/resources/css/estrutura-app.css`. Ela importa só `theme` e `utilities` do Tailwind, com prefixo `tw` e sem Preflight. Assim nenhum reset atinge telas ainda não migradas.
- O build é `scripts/tailwind/build-estrutura.mjs`. A prévia usa `scripts/tailwind/build.mjs`. O Tailwind CLI e os pacotes ficam travados em `scripts/tailwind/package-lock.json`.
- O CSS legado continua em `public/css/app-conteudo-legado.css` (cópia de `app.css` sem a estrutura). O layout autenticado carrega o legado, `tabelas.css` e `estrutura-app.css`.
- A saída é versionada pelo helper `@ativo('css/...')`. Não há CDN nem servidor de desenvolvimento em homologação ou produção.
- Nomes próprios do projeto ficam em pt-BR: tamanhos de botão `pq`, `md`, `gd`, `xg` e largura `lg`. Não traduza APIs ou utilitários oficiais do framework.
- Ações SMALL em tabelas e cards: altura mínima 32 px, texto `text-sm`, ícone 16 px, alvo de toque 44 px.
- Migre por família completa. Remova o CSS antigo da família migrada. Não use `!important` global.

## 4. Layout e navegação responsivos

### 4.1 Régua e área útil

Régua única de larguras: `xs` abaixo de 480 px, `sm` 480 a 767, `md` 768 a 1023, `lg` 1024 a 1439, `xl` 1440 a 1919 e `2xl` a partir de 1920.

Decisões do responsável:

- A tela ocupa toda a área útil do viewport (30/10/2026 e 03/11/2026). Não use `max-width` fixo em painéis, formulários principais ou listas.
- Margens responsivas e limite de leitura de parágrafos são permitidos. Controles, diálogos e imagens mantêm limites próprios.
- Grupos de trabalho ficam empilhados. Campos ficam responsivos dentro deles.
- Valide desktop amplo, menu recolhido e celular, sem overflow e sem ação coberta pelo rodapé.
- Remover tudo que remete a gov (faixa, textos, exemplos). Login sem estatísticas, sem "gov.br" e sem aba Cadastrar.

### 4.2 Estrutura da área logada (TW2)

| Região | Comportamento |
|---|---|
| Cabeçalho (64 px, único) | Botão de recolher menu, marca, alternador de tema e avatar da conta |
| Avatar da conta | Abre nome, e-mail, perfil, Meu perfil, Configurações (com `rede.configurar`), Assinatura (titular), Trocar vínculo (só com mais de um vínculo elegível) e Sair (POST) |
| Menu lateral (desktop e tablet, 768 px ou mais) | Em fluxo, 256 px expandido ou 80 px recolhido. Empurra o conteúdo e não o cobre. A preferência fica no navegador |
| Celular (até 767 px) | Barra inferior com 4 atalhos do perfil e "Mais", que abre uma folha (`dialog`) com a lista completa |
| Contexto ativo | Bloco "Meu espaço" informativo na lateral e no topo do conteúdo (escola e perfil) |
| Rodapé | `Gabarito360 - v0.1.0 - Homologação` (texto literal do responsável). Aparece só quando há `release.sha`. Fica oculto até 767 px |
| Pular para o conteúdo | Link inicial `#conteudo-principal` |

O menu vem de `App\Support\Acesso` (`apps/web/app/Support/Acesso.php`). Cada item exige permissão efetiva. A API continua sendo quem decide o acesso aos dados. A versão do produto vem de `apps/web/config/versao.php`; a fase e o SHA vêm de `release.php`.

### 4.3 Verificação contínua

Antes de cada PR, rode `bash scripts/auditoria/rodar.sh <branch>`. A auditoria mede as telas em seis larguras (375, 768, 1280, 1920, 2560 e 3840 px). Ela procura rolagem lateral, erro de JavaScript e excesso de fotos pedidas ao abrir. A CI roda os testes e `scripts/verificar_telas.py` em cada PR. Telas geradas têm dados reais e nunca entram no repositório.

## 5. Tabelas e listas (TBL1 e TBL2)

### 5.1 Componente `x-tabela`

`apps/web/resources/views/components/tabela.blade.php` padroniza as tabelas operacionais da área logada.

| Prop | Padrão | Efeito |
|---|---|---|
| `titulo` | obrigatório | Identifica o conjunto (legenda acessível e rótulos de controles) |
| `modo` | `lista` | `lista`, `selecao`, `matriz` ou `compacta` |
| `completa` | `false` | Liga controles locais (20/50/100/Todos, ordenação, busca) somente se toda a coleção está no HTML |
| `integrada` | `true` | Contorno discreto; toolbar, tabela e rodapé unidos; sem outro card |
| `interna` | `false` | Aplica recuo de 24 px (desktop) ou 16 px (celular) quando o pai não tem padding |

Quatro tipos de superfície:

1. **Lista operacional** — registro identificável, situação, dados secundários e ações. No celular vira cards.
2. **Seleção em lote** — identidade e caixa de seleção. Não promete selecionar todos os resultados quando só a página está selecionada.
3. **Matriz comparativa** — relações aluno x prova x disciplina. Fica em quadro rolável com região focável. O resumo alternativo em cards foi adiado.
4. **Resumo informativo** — lista curta, sem busca ou paginação decorativa.

Regras:

- A mesma árvore HTML serve tabela e cards. Não duplique formulários, IDs ou seleções.
- Rótulos de célula vêm do servidor. A coluna principal é explícita.
- Sem JavaScript, todas as linhas e ações continuam no HTML.
- Controles locais (paginação, ordenação A-Z, tamanho de página) só existem em coleção integral: Provas, Alunos, Turmas e grupos de Equipe. Clientes, catálogo e cópias mantêm a paginação do servidor.
- Erro HTTP nunca aparece como lista vazia ou total zero. Estado vazio, sem resultado e erro são distintos.
- Filtros ativos ficam visíveis. O link Limpar usa a mesma condição de "há filtros" (inclui `ativo=0`).
- Inventário: 50 instâncias de `x-tabela` em 41 views e parciais. A tabela ilustrativa da home é publicitária e fica fora do contrato.
- Filtros GET e tabela das listas Alunos, Provas e Turmas ficam no mesmo card (um só padding).
- Impressão A4 anula contorno e recuo novos. Linhas ocultas pela paginação local voltam a aparecer ao imprimir.

### 5.2 Coluna de ações (TW3)

Decisão do responsável: Ações é sempre a **última coluna, à direita**, com largura para todos os botões. O título e os botões ficam centralizados na coluna. Nos cards, as ações ficam no rodapé esquerdo.

Ordenação direta nos cabeçalhos de dados: alterna crescente e decrescente, usa `aria-sort`, foco e anúncio do resultado. Números e datas usam valor semântico. Ausências vão ao fim e empates mantêm a ordem original. Coleção completa ordena antes da paginação local; página parcial informa "Ordenação dos registros desta página".

### 5.3 Ações dentro de tabelas e cards

- Consulta e edição usam texto curto ou ícone com texto. Ícone isolado só quando reconhecível e com nome acessível.
- Publicar, Salvar, Transferir, Encerrar e Retirar mantêm texto e efeito explícitos.
- Editar e Arquivar/Desarquivar turma sempre têm ícone e texto.
- Altura da linha é natural, definida pelo maior conteúdo. Sem reserva genérica de largura e sem truncar para forçar compactação.
- Dados secundários não somem no celular. Campos não autorizados continuam ausentes (a ocultação não é só visual).

## 6. Carregamento e estados de erro (UI1)

- A casca da aplicação (cabeçalho e menu) é desenhada a partir do contexto vigente, sem consultar a API para montar opções de troca.
- O preloader de tela inteira aparece só na aba que acabou de confirmar a seleção ou troca de vínculo. Ele usa a marca oficial, centralizada, sem cartão, com pulso suave por três ciclos e sem tempo mínimo artificial.
- Navegações comuns mantêm a casca e mostram esqueleto no conteúdo pendente (`navegacao/_esqueleto.blade.php`).
- Componentes: `x-carregando` (`role="status"`, `aria-busy`) e `x-estado-tela` (erro estático, com marca, código, título, mensagem e ações).
- Erros de HTTP têm views próprias em `resources/views/errors/`: 401, 403, 404, 419, 429, 4xx, 500, 503, 5xx e `estado`. Elas mostram `Retry-After` quando existe e nunca expõem detalhes internos.
- Falha interrompe a animação e oferece ações seguras de leitura e retorno. Não reenvie POST. 401 e 419 levam ao login. 409 de aba antiga é preservado.
- Região que falha não apaga as regiões já carregadas. Zero, vazio, erro e carregamento são estados diferentes.
- Para `noscript` e `prefers-reduced-motion`, o estado é estático.
- JavaScript executável fica em arquivos externos: `carregamento.js`, `estado-tela.js`, `entrada-vinculo.js`.

## 7. Gráficos (ECharts)

- Biblioteca: Apache ECharts 6.1.0, servida localmente (`public/js/libs/echarts-6.1.0.common.min.js` e o pacote reduzido `echarts-g360.min.js`). Sem CDN. O CI reconstrói o artefato a partir de `scripts/graficos/`.
- Componente: `<x-grafico :dados="[...]" descricao="..." :altura="240">`. A configuração central (cores, fonte, claro e escuro, tooltip) está em `public/js/graficos.js`. O ECharts só carrega nas telas com gráfico.
- Use `<x-grafico>` apenas no HTML. Componente Blade dentro de JavaScript quebra a tela.
- Saída em SVG, nítida na impressão A4.
- Regras de desenho: uma cor por série, na ordem da seção 2.1; uma turma ou prova mantém a mesma cor; um eixo só; rótulo de valor só onde ajuda; legenda com duas séries ou mais; verde, amarelo e vermelho só para situação, sempre com texto.
- A média comparada à meta **não** usa rosca. Use número grande, barra e marca da meta. O andamento da correção também é barra de progresso.
- Cada gráfico mantém os mesmos números em tabela ou texto, para leitura sem cor e para leitor de tela. O componente compartilhado oferece "Ver os números" em `<details>`. Nos widgets dos painéis esse botão foi removido; a alternativa fica em ARIA (veja o documento de painéis).
- Gráficos se ajustam à área do card, sem rolagem interna. Não inicialize gráfico com dimensão zero em aba oculta.
- Gráficos de barras dos widgets têm título nos dois eixos, invertidos corretamente em modo horizontal e vertical.
- Cores do modo escuro: `#5f8fe8`, `#14a88f`, `#b98527`, `#a278d4` e `#d9713c`.
- O PDF gerado pela API usa outro caminho (Python) e não passa pelo ECharts.

Outras bibliotecas locais usadas na interface: GridStack 14.1.0 e SortableJS 1.15.7 (editor de painéis), Solid e solid-toast (notificações), Calendar.js 1.1.0 (agendas demonstrativas), Cropper 2.3.1 (recorte de foto), heic-to 1.6.7, pdf-lib 1.17.1 e PDF.js 6.5.438. Os pacotes de construção ficam em `scripts/paineis-grid`, `scripts/notificacoes`, `scripts/calendario` e `scripts/graficos`.

## 8. Componentes Blade compartilhados

Ficam em `apps/web/resources/views/components/`.

| Componente | Uso |
|---|---|
| `pagina` | Cabeçalho de página: título, subtítulo e ações |
| `indicadores` / `indicador` | Faixa de indicadores (KPIs) |
| `cartao` | Superfície de card |
| `aviso` | Aviso de sucesso, erro, atenção e informação (`role` status ou alert) |
| `tabela` | Tabela e lista responsivas (seção 5) |
| `grafico` | Gráfico ECharts (seção 7) |
| `marca` | Marca oficial (seção 2.2) |
| `avatar`, `avatar-usuario`, `avatar-turma`, `seletor-avatar`, `preparo-foto` | Imagens de aluno, usuário e turma, com recorte |
| `estado-tela`, `carregando` | Estados da seção 6 |
| `contexto-ativo`, `revisao-acesso` | Contexto de vínculo e revisão da sessão em formulários |
| `painel-secao`, `painel-widget`, `painel-mover-grupo` | Blocos dos painéis personalizáveis |
| `arquivamento`, `orientacao`, `erro-campo`, `icone`, `icone-estrutura` | Diálogo de arquivar, escolha de orientação do PDF, erro de campo e ícones (SVG inline até a migração para a fonte G360 Icones, ADR-0063) |
| `menu-publico`, `rodape-publico` | Páginas públicas |

A página interna `/referencia-visual` (rota `referencia`, view `referencia.index`) é a vitrine dos componentes base.

Avisos compartilhados têm `margin-block: 16px`. Em containers com `gap`, o próprio gap separa os elementos.

## 9. Acessibilidade e interação

- JavaScript executável fica em arquivos externos. Não use `<script>` com lógica nem `onclick` nas views. Passe configuração por `data-*` ou por bloco `application/json` escapado.
- Foco visível em todos os controles. Alvo de toque de 44 px.
- Menus, abas e tabelas funcionam por teclado: abas com setas, Home, End, Enter e Espaço; popovers com Escape e clique fora.
- Título de cartão, gráfico ou KPI abre uma ajuda contextual (popover) por clique, toque, Enter ou Espaço. Esse padrão substitui "Saiba mais" expansível. Não transforme títulos de navegação em ajuda.
- Notificações flutuantes (toast) têm quatro variantes: informação, sucesso, atenção e erro. Cada uma distingue-se por cor, ícone, título e anúncio. Erros e conflitos persistem no aviso junto aos controles; sucesso e informação usam toast. A região de toasts é fixa e fora do fluxo, e vai para o topo do diálogo aberto quando preciso.
- Confirmação nunca usa `confirm()` do navegador. Use diálogo próprio com nome do alvo e efeito.
- Estado nunca depende só de cor. Gráficos têm descrição e alternativa em texto.
- `prefers-reduced-motion` é respeitado em marca, carregamento e gráficos.
- Screenshots não comprovam acessibilidade completa. A revisão independente registra o que foi conferido.

## 10. Regras de UI/UX vigentes no AGENTS.md

O AGENTS.md é a fonte da regra. Os pontos abaixo resumem e citam as seções dele. Não os remova de lá.

| Seção do AGENTS.md | Regra |
|---|---|
| JavaScript da aplicação | JavaScript executável em arquivos externos; sem lógica inline |
| UI, UX e Product Design | Toda mudança de interface exige consulta prévia e revisão final de agente especializado em UI/UX e Product Design. Quem implementa não aprova a própria interface. A revisão final exige evidência atual de desktop e celular, ações principais e erros |
| Área útil e composição | Tailwind como base; paleta preservada; tela em toda a área útil; ajuda pelo título; prévia isolada antes de aplicar uma identidade nova |
| Tabelas, cards e avisos | `x-tabela` integrado ao card; recuo de 24/16 px só onde o pai não tem padding; listas viram cards no celular com todas as ações; avisos com margem de 16 px |
| Continuidade e publicação | Homologação primeiro, com backup; produção só por decisão explícita |
| Padrão de escrita (CLAUDE.md) | ASD-STE100 moderado |
| Prints (CLAUDE.md) | Nenhum print é mantido; a conferência visual é registrada em texto |

## 11. Inventário das telas

Os caminhos abaixo são os nomes de rota e as views reais (`apps/web/routes/web.php`). Na navegação, a URL visível usa o prefixo `/app` e o contexto fica só na sessão. O contrato de navegação (UR4) está descrito na [arquitetura](README.md).

### 11.1 Painéis por perfil (rota `painel`)

O controlador `DashboardController` escolhe a view pelo perfil ativo (FN-052).

| Perfil | View do painel | Atalhos do celular (4 + Mais) |
|---|---|---|
| Administrador da Rede | `dashboard.admin` | Painel, Escolas, Relatórios, Equipe |
| Secretário de Educação | `dashboard.secretaria` | Painel, Relatórios, Escolas |
| Diretor de Núcleo | `dashboard.diretor-nucleo` | Painel, Escolas, Relatórios, Equipe |
| Diretor Escolar | `dashboard.diretor-escolar` | Painel, Provas, Turmas, Relatórios |
| Vice-diretor escolar | `dashboard.diretor-escolar` | Painel, Provas, Turmas, Relatórios |
| Coordenador | `dashboard.coordenador` | Painel, Corrigir, Provas, Turmas |
| Professor / Professor autônomo | `dashboard.professor` | Painel, Corrigir, Provas, Turmas |
| Secretário escolar / Auxiliar de secretaria | `dashboard.secretario-escolar` | Painel, Turmas, Alunos |
| Aplicador | `dashboard.aplicador` | Painel |
| Aluno | `dashboard.aluno` | Painel |
| Desenvolvedor, Suporte, Financeiro | `dashboard.interno` | Painel, Clientes (Desenvolvedor também Acessos internos) |

Sem vínculo escolhido, a tela `dashboard.selecionar-contexto` pede a seleção.

### 11.2 Telas do mockup e rota atual

| Mockup (30 telas) | Rota Web atual | Perfis principais |
|---|---|---|
| 01 login | `login` (`/login`) | Público |
| 02 a 07, 29 painéis | `painel` | Veja 11.1 |
| 08 provas | `provas.index` | Professor (suas provas); Coordenação e Direção (escola); Admin e Núcleo (todas) |
| 09 criar prova | `provas.create`, `provas.curriculo.edit` | Professor, Coordenador |
| 10 gabarito | `provas.gabarito.show`, `provas.gabarito.edit` | Professor, Coordenador, Direção |
| 11 acompanhar correção | `correcao.show`, `correcao.revisao`, `correcao.tentativas` | Professor, Coordenador |
| 12 acompanhar por turma | `correcao.turma` | Coordenador, Diretor Escolar |
| 13 turmas | `turmas.index` | Professor (suas turmas); Coordenação e Direção (escola); Admin e Núcleo (rede) |
| 14 detalhe da turma | `turmas.show`, `turmas.edit` | Idem |
| 15 e 16 escolas | `escolas.index`, `escolas.show`, `escolas.edit` | Admin, Núcleo; Diretor Escolar (a sua) |
| 17 e 18 cadastrar e editar aluno | `alunos.create`, `alunos.edit` | Coordenador, Direção, Secretaria |
| 19 detalhe do aluno | `alunos.show` | Professor (seus alunos), Coordenador, Direção |
| 20 e 21 resultado | `resultados.show`, `resultados.historico` | Professor, Coordenador, Direção; Aluno (o próprio) |
| 22 e 23 relatórios de prova | `relatorios.prova`, `relatorios.turmaProva` | Professor, Coordenador, Direção |
| 24 perfil | `perfil` | Todos |
| 25 configurações | `configuracoes.index` | Administrador da Rede (permissão `rede.configurar`) |
| 26 a 28 equipe | `membros.index`, `membros.create`, `membros.edit`, `membros.permissoes` | Direção, Coordenação, Admin |

O mockup 21 (resultado dinâmico) é variante do 20 e não tem rota própria.

### 11.3 Telas sem mockup (aprovadas depois)

| Grupo | Rotas principais |
|---|---|
| Relatórios consolidados e da turma | `relatorios.turma`, `relatorios.aluno`, `relatorios.escola`, `relatorios.nucleo`, `relatorios.rede` |
| Correção por câmera e fila | `correcao.index`, `correcao.upload`, `correcao.tentativa`, `correcao.envio` |
| Disciplinas e áreas do conhecimento | `disciplinas.*`, `areas.*` |
| Vínculos e convites | `vinculos.index`, `vinculos.convite`, `membros.convite-vinculo` |
| Funções e exceção funcional | `funcoes.index` |
| Catálogo de instituições | `instituicoes.*`, `catalogo-pedidos.*`, `catalogo-atendimento.*`, `catalogo-gestao.*` |
| Cópias entre espaços | `copias.*` |
| Clientes e acessos internos | `clientes.*`, `internos.*` |
| Pedidos de titulares (LGPD) | `pedidos.*` |
| Assinatura e checkout | `assinatura.index`, `checkout.*`, `planos.index` |
| Público | `home`, `planos.index`, `leads.index` (Fale conosco), `documentos.validar` |
| Apoio | `referencia`, `trocar-senha`, `email.confirmar` |

Os painéis de turmas e da turma têm composição própria: veja [Painéis e editor](10-paineis-e-editor.md).

## 12. Divergências encontradas

| # | Documento antigo ou regra | Código atual | Vale |
|---|---|---|---|
| 1 | CLAUDE.md e doc 26 dizem Figtree como fonte principal | A área logada (`.tw2-app`) usa pilha de fontes do sistema (`ui-sans-serif, system-ui`). Figtree continua nas páginas públicas e de acesso, carregado do Google Fonts, e no nome da marca | Código. Decisão do TW2: fonte de sistema na estrutura nova |
| 2 | Doc 27: ECharts "sem depender de CDN" | `layouts/auth.blade.php` e `layouts/public.blade.php` ainda carregam Figtree do Google Fonts (CDN de fontes, não de gráficos) | Pendente de revisão em TW6 |
| 3 | Doc 23: faixa de largura 1600 a 1920 px e menu opcional no topo | O responsável decidiu "tela toda" e menu lateral. A escala por `zoom` (1,125 a 1920 px, 1,25 a 2560, 1,5 a 3200) do legado não rege mais a casca TW2 | Código e decisão posterior |
| 4 | Doc 177 diz "celular não força Cards" na relação de turmas | `painel-grade.js` apresenta Cards quando `max-width: 767px`; o desktop mantém Tabela por padrão | Código. Veja o documento de painéis |
| 5 | Doc 131 cita o modo `matriz` e `selecao` | O componente também aceita `compacta` e só liga controles locais nos modos `lista`, `selecao` e `compacta` | Código |
| 6 | Doc 162 cita atlas `brasoes-v1.png`, `educadores-v1.png`, `estudantes-v1.png` | A pasta `public/images/avatares/` também tem as versões `*-v2.webp` | Código (atlas v2 em uso quando presente) |
| 7 | Doc 185 cita `resources/css/app.css` como importador do Tailwind | Esse arquivo ainda é o scaffold Laravel (fonte Instrument Sans em `vite.config.js`). O Tailwind efetivo da interface é `estrutura-app.css`, compilado por `scripts/tailwind` | Código |
| 8 | Doc 01: 30 telas, Landing e SaaS "pendentes de aprovação visual" | Todas existem como rotas; a Landing virou a apresentação pública da seção 2.3 | Código |
| 9 | Doc 01: login com aba "Cadastro" e estatísticas | Removidas por decisão de 30/10/2026 | Decisão posterior |
| 10 | Doc 164 cita a home com degradê `#1D4F9E` para `#001534` na abertura | Confere em `--g360-access-gradient` para acesso. Páginas internas não têm degradê | Confere |

Os históricos de execução (CI, recibos, números de PR e de testes) dos documentos 23, 26, 27, 131, 149, 161, 162, 164, 174, 184 a 188 estão nos `planejamento.md` das funcionalidades FN-070 a FN-076 e no Git.
