> **Design system do Gabarito360** — espelho versionado do artifact "Gabarito360 — Design System" no claude.ai, extraído do app atual (`apps/web` na `main`, publicado na homologação) em 11/11/2026. Regras: [ADR-0056](../docs/decisoes/0056-app-atual-como-referencia-visual.md) e [ADR-0057](../docs/decisoes/0057-app-responsivo-em-todas-as-faixas.md). As prévias em `components/*/preview.html` carregam o CSS real de `apps/web/public/css/`. Os arquivos antigos estão em `historico/`.

O Gabarito360 é uma plataforma de avaliação escolar. Ela lê cartões-resposta por foto, gera e aplica provas e mantém um banco de questões. Escolas, secretarias e núcleos de educação usam o serviço. O professor usa o celular na sala; a coordenação usa o notebook da escola; a secretaria usa monitores grandes.

Este design system descreve o app como ele está hoje: `apps/web` na `main` (commit `37ba645`), publicado na homologação. Os valores vêm do código. A fonte de cada valor está na nota de uso do token ou no README do componente.

## Regras de origem

### O app atual é a referência (ADR-0056)

1. Use o app atual como referência visual: as views Blade, os componentes e o CSS de `apps/web/public/css/`.
2. Trate `mockups/` e `style-system/` como históricos. Consulte-os só para escopo e textos originais.
3. Em todo mockup novo, use a casca real (cabeçalho, lateral, navegação inferior, rodapé), os componentes reais (`x-pagina`, `x-aviso`, `x-tabela`, cards, botões, modais) e o CSS real.
4. Limite o CSS próprio do mockup ao que é novo. Use só os tokens deste sistema.
5. Em conflito, vale esta ordem: mockup novo aprovado pelo responsável; app atual; `docs/`; `mockups/` e `style-system/`.

### O app é responsivo (ADR-0057)

1. Toda tela e todo mockup atendem a todas as faixas: celular pequeno, celular, tablet, notebook, desktop e telas grandes até 3840 px.
2. Confira 320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px, nos temas claro e escuro.
3. Registre a conferência por texto, sem print (ADR-0049).
4. Use só os cortes canônicos. Um corte novo exige registro neste sistema e no doc 09.

A tabela completa de faixas e cortes está na seção Responsivo.

## Como usar este sistema

1. Carregue o CSS real na ordem da área logada: `app-conteudo-legado.css`, `tabelas.css`, as folhas da tela, `marca.css` e `estrutura-app.css`. A prévia deste sistema usa `components/bundle.css`, que junta essas folhas sem mudar regras.
2. Ponha `class="area-logada tw2-app tela-…"` no `<body>`. A classe `.tw2-app` fixa a fonte do sistema e a escala 1.
3. Ponha o tema em `<html data-theme="light|dark">`. O padrão é claro. O app não segue `prefers-color-scheme`.
4. Escolha a família dos componentes pela tela. `body.tela-turmas` e `body.tela-turma` usam a família TW2. As outras telas usam a família legada. O mesmo nome de classe muda de aparência (seção Divergências, item 1).
5. Use os tokens pelo nome da variável CSS: `var(--accent)`, `var(--space-4)`, `var(--radius-md)`. Os nomes dos tokens são os nomes do código.

## Fundamentos de conteúdo

### Idioma e padrão de escrita

- Escreva em português do Brasil.
- Siga o ASD-STE100 moderado (ADR-0050): uma ideia por frase; voz ativa; imperativo nos passos; passo com até 20 palavras e descrição com até 25.
- Use um termo por conceito. Defina a sigla na primeira vez.
- Escreva textos curtos. Diga a causa e a ação. Exemplo real: "Inclua a folha inteira e os quatro quadrados pretos."
- Não use frase de efeito, emoji nem número inventado ("340 escolas"). Use dados do cadastro (doc 09 §2.1).

### Termos

| Termo | Use para | Exemplo real |
|---|---|---|
| Cartão-resposta (ou cartão) | a folha em que o aluno marca as respostas | "Cartão-resposta completo", "Fotografar cartões" |
| Gabarito | só o conjunto das respostas corretas de uma prova | "Gabarito oficial" |
| Folha | na interface do Leitor360, qualquer página lida | "Inclua a folha inteira" |
| Leitura | o resultado da leitura óptica de um cartão | "Leitura do sistema: B e C (marcação incerta)" |
| Revisão de leitura | a conferência humana das leituras duvidosas | "Revisar leituras" |

Não use "gabarito" para o cartão do aluno. O botão "Visualizar gabarito" da revisão abre o cartão; a FN-079 o renomeia para "Ver cartão inteiro" (seção Divergências).

### Rótulos e mensagens

- Botões com verbo: Salvar, Cancelar, Aplicar filtros, Limpar campos, Fotografar cartões.
- Título padrão dos toasts: Concluído, Não foi possível concluir, Informação, Em andamento.
- Rodapé de versão literal: `Gabarito360 - v0.1.0 - Homologação`.
- Confirmação com o nome do alvo e o efeito: "Arquivar turma 7º ano A? O histórico será preservado e o cadastro sairá das listas de ativos."

## Fundamentos visuais

### Cor por função

- `accent` (#1d4f9e; escuro #86b1f2) é a ação principal, o link, o item ativo e o foco.
- `atencao` (#0b7f75; escuro #4cc7b9) marca o que pede a atenção do usuário. Use com `atencao-light` como fundo.
- `success`, `warn` e `danger` só indicam situação. Junte sempre texto ou ícone. Não comunique estado só por cor.
- `fg` é o texto principal; `fg-2` o secundário forte; `muted` o auxiliar (rótulos, subtítulos, breadcrumb).
- `serie-1` a `serie-5` colorem as séries de gráfico. Dê alternativa em texto a todo gráfico ("Ver os números").
- Não use degradê azul nas páginas internas. O degradê da marca (`linear-gradient(160deg,#1d4f9e 0%,#001534 100%)`, `marca.css:3`) fica só na abertura do site e nos painéis de acesso. Ele não é token de cor.

### Superfícies

- `bg` é o fundo da página; `surface` o fundo de cards, cabeçalho e lateral; `surface-2` o hover, o cabeçalho de tabela e os blocos de dados.
- No tema escuro, a lateral e o cabeçalho usam a superfície escura `surface` (#182234). Não troque essa superfície (ADR-0036).
- Bordas: `border` em controles, tabelas e casca; `border-soft` em cards e divisores.

### Tema escuro

- O tema escuro vem de `html[data-theme="dark"]` (`app-conteudo-legado.css:978`). O botão do cabeçalho alterna o atributo e grava `g360-theme`.
- O escuro redefine os mesmos tokens. Não fixe cor clara em hexadecimal num componente.
- O escuro não redefine `success`, `warn`, `danger` nem `faixa`. Avisos usam `texto-sucesso` e `texto-erro` no escuro. Badges de sucesso e erro ficam abaixo de 4,5:1 (seção Divergências, item 6).

### Contraste

- O texto normal tem 4,5:1 ou mais sobre o fundo da nota do token.
- Pares do código abaixo do mínimo, mantidos e sinalizados: `success` sobre `success-light` (4,02:1 claro; 3,39:1 escuro); `atencao` sobre `atencao-light` no claro (4,21:1); `danger` sobre `danger-light` no escuro (3,34:1); `danger` sobre `surface` no escuro (2,90:1); `border` sobre `surface` (1,53:1 claro; 1,39:1 escuro).
- Foco: `outline: 2px solid var(--accent); outline-offset: 2px` na casca; 3 px nas tabelas e na revisão. `accent` tem 7,88:1 sobre `surface` no claro e 7,28:1 no escuro.

### Tipografia

- Área logada: fonte do sistema (`ui-sans-serif, system-ui, sans-serif`, família `sans`), definida em `.tw2-app`.
- Páginas públicas e de acesso: Figtree 400 a 800, do Google Fonts (família `publico`).
- Marca: G360 Figtree 800 e G360 Albert Sans 800 (só no "6"), arquivos em `fonts/`.
- Números: `--font-mono` é a fonte do texto, com algarismos tabulares (`font-variant-numeric: lining-nums tabular-nums`).
- Título de página: `tipo-titulo-pagina` (24/32 600). Subtítulo: `tipo-subtitulo-pagina` (14/20 `muted`). Corpo: `tipo-corpo` (16/1,5).
- A escala `--text-*` (12 a 36 px) só aparece nos componentes `g-*`. O resto usa valores em px.

### Espaço, raio e sombra

- Espaço: `space-1` a `space-12` (4 a 48 px). Padding de card: `space-6` (24). Padding de célula: `space-4` (16).
- Raio: `radius-sm` (4) no badge; `radius-md` (6) em botão, campo, aviso e toast; `radius-lg` (8) em card e modal legado; `radius-pill` em trilhas. Diálogos novos usam 12 px, fora da escala.
- Sombra: `shadow-sm` é `none` (card legado sem sombra). `shadow-md` fica no toast e no diálogo de arquivar.

### Área útil e casca

- As telas ocupam toda a área útil, em largura e altura (ADR-0021). Limite só a largura de leitura de parágrafos, controles, diálogos e imagens.
- Cabeçalho de 64 px (`cabecalho-altura`). Lateral de 256 px (`lateral-expandida`) ou 80 px (`lateral-compacta`), em fluxo, sem backdrop.
- Até 767 px: navegação inferior com 4 atalhos e Mais; lateral e rodapé de versão ocultos.
- Alvo de toque: 44 px (`alvo-toque`) em `pointer:coarse` e até 767 px. Botão SMALL (`pq`): 32 px (`alvo-pq`) com alvo de 44 px.

### Movimento

- Transições de .15 s em cor, fundo e borda. Barra de progresso: .25 s.
- Carregamento: `g360-respirar` e `g360-esqueleto`, 1,8 s. Animação da marca: 1,05 s, sem repetir.
- `prefers-reduced-motion` desliga animações e transições na casca, no carregamento, nos toasts, na marca e nos gráficos.

### Impressão

- `@media print` esconde a casca (cabeçalho, lateral, rodapé, navegação, contexto). A4 com `@page`. Tabelas sem rolagem.

## Iconografia

O Gabarito360 usa **uma família de ícones só**: a G360 Icones, própria e entregue como fonte ([ADR-0063](../docs/decisoes/0063-familia-de-icones-propria-em-fonte.md)).

### A família G360 Icones

- **Duas variantes, os mesmos códigos:** G360 Icones (contorno, padrão) e G360 Icones Preenchido (item ativo do menu e destaque).
- **Arquivos do app:** `apps/web/public/fonts/icones/` (WOFF2 para o app, TTF para instalar no Windows e no CorelDRAW) e `apps/web/public/css/g360-icones.css`.
- **Fonte única:** `style-system/icones/`. Os desenhos de traço ficam em `src/`. A especificação fica em [`DIRETRIZ.md`](icones/DIRETRIZ.md). O catálogo de ids fica em `catalogo.json`.
- **Especificação:** grade 24 × 24, área útil 2..22, traço 1,8 com pontas e junções redondas, folga mínima de 1,2 px entre traços.
- **Marcação:** `<span class="g360i g360i-<id>" aria-hidden="true"></span>`. Para a variante cheia, acrescente `g360i--preenchido`.
- **Duotônico:** a classe `g360i--duo` põe o preenchido no tom claro sob o contorno no tom forte.
- **Cor só por token.** O padrão é `currentColor`. Os tons são `g360i--azul` (`--accent`), `--verde` (`--texto-sucesso`), `--vermelho` (`--texto-erro`), `--amarelo` (`--warn-fg`), `--laranja` (`--serie-5`), `--lilas` (`--purple-fg`) e `--marca` (`--g360-blue`). Não há token rosa. Não ponha cor fixa em regra de `.g360i`.
- **Códigos fixos:** cada id tem um código na área de uso privado, a partir de U+E001, em `style-system/icones/registro.json`. Não mude um código existente. Um ícone novo entra no fim do registro.
- **Um conceito, um desenho, um id.** Cada item do menu tem o próprio ícone.
- **Proibido:** emoji, caractere Unicode (✕, ×, ←, →, ✓ e outros) ou SVG avulso como ícone. Não há exceção. O Fechar do visor da câmera usa o ícone `fechar`.
- Ficam fora da fonte o símbolo da marca (SVG próprio, seção Marca) e as formas CSS que não são ícone, como o ponto de situação e a amostra de cor da legenda.
- Tamanhos: 16 px em ações de página e tabela; 18 px em `.btn` legado e no alternador de tema; 20 px na navegação; 22 px no conteúdo; 36 px no estado vazio; 40 px em `.icone-grande`.
- Acessibilidade: todo ícone tem `aria-hidden="true"`. Quando o ícone é o único conteúdo do controle, o nome acessível fica no controle.

### Como adicionar um ícone

Siga [`style-system/icones/COMO-ADICIONAR.md`](icones/COMO-ADICIONAR.md). Resumo:

1. Procure o conceito no `catalogo.json` e no mapa de metáforas da diretriz.
2. Desenhe no lote do assunto, com os motivos de `motivos.py`.
3. Confira na prancha, nas duas variantes e nos dois temas.
4. Compile a fonte e o CSS. Copie os arquivos compilados para `apps/web/public/`.

### Estado atual do app (migração pendente)

As telas ainda usam os dois conjuntos antigos em SVG inline. A troca pelo glifo da fonte é de outra funcionalidade. Até lá, `x-icone` e `x-icone-estrutura` continuam como abaixo. Não crie ícone novo nesses componentes.

| Componente | Desenho | Marcação | Fonte |
|---|---|---|---|
| `x-icone-estrutura` | Material Symbols (Apache-2.0), preenchido | `<svg class="icone-estrutura" viewBox="0 -960 960 960" width="20" height="20" fill="currentColor" aria-hidden="true">` | `resources/brand/estrutura-icones.json` |
| `x-icone` | desenho próprio, traço 1,8 com pontas redondas | `<svg class="nav-icone" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">` | `resources/views/layouts/_icone.blade.php` |

- Ícones de estrutura (17): menu, mais, casa, prova, camera, turma, aluno, escola, equipe, grafico, ajustes, cadeado, compartilhar, lanterna, transferir, sair, fechar. Uso: casca (cabeçalho, lateral, navegação inferior, menu da conta).
- Ícones de traço (35 e o padrão "três pontos"): casa, prova, lanterna, camera, turma, aluno, escola, equipe, grafico, imprimir, ajustes, lixeira, cadeado, compartilhar, editar, adicionar, seta, atualizar, voltar, fechar, confirmar, salvar, transferir, arrastar, fixar, altura-livre, barras-horizontais, barras-verticais, filtro, limpar, ver, arquivo, situacao, pdf. Uso: conteúdo, ações e estados vazios.
- Alternador de tema: lua (claro) e sol (escuro), traço 2, 18 px, injetados por `public/js/app.js:162-163`.

## Marca

- Use o componente `x-marca` (seção de componentes, Marca). Ele injeta o vetor oficial `resources/brand/gabarito360-icon.svg`.
- O grupo de assets Marca tem os arquivos de `apps/web/public/brand/`: símbolo em SVG e PNG nos dois temas, marca para e-mail e imagem de compartilhamento.
- O nome é texto HTML, não imagem: `Gabarito<span class="g360-numeros">3<span class="g360-six">6</span>0</span>`.
- Não aplique identidade nova direto nas telas operacionais. Faça antes uma prévia isolada com dados fictícios (ADR-0027).
