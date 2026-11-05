# H1 — identidade oficial: componentes, fontes e assets

## Escopo e autoria

Integração de branding a partir do pedido integral `Texto colado.txt` e do ícone oficial `icone-gabarito360.svg`. Não há mudança de autenticação, rotas, banco, leitura ou notas. A tipografia do corpo permanece como estava: as famílias locais abaixo têm nomes CSS exclusivos e são usadas somente na marca. Os cabeçalhos PDF/impresso, regras `.ic`, título de resultado centralizado e caixas de aluno/nota pertencem ao incremento paralelo descrito no doc161.

O autor destes componentes não concede aprovação visual independente à própria implementação. O parecer final deve vir do agente especializado independente ou do responsável, após capturas atuais.

## Componente oficial

`apps/web/resources/views/components/marca.blade.php`:

```blade
<x-marca variant="horizontal" size="sm" theme="light" />
<x-marca variant="icon" size="md" theme="auto" />
<x-marca size="lg" theme="dark" :animated="true" />
```

- `variant`: horizontal ou icon; `size`: sm/md/lg; `theme`: auto/light/dark.
- Wordmark HTML: Gabarito e3/0 em Figtree800; apenas6 em AlbertSans800. Azul-marinho `#001534`, azul `#0588FC`, branco `#FFFFFF`.
- `marca.css` centraliza tokens, WOFF2, tamanhos, versões e adaptação do header/menu recolhido.
- Um nome acessível Gabarito360; vetor/texto internos decorativos evitam leitura duplicada.
- Animação opt-in de1,05s, sem loop; círculo aparece, check entra e pequena confirmação. `prefers-reduced-motion` desativa. Carregamento comum não ativa confirmação animada nem remove seus estados de progresso.
- Login/recuperação usam versão dark explicitamente sobre o degradê de acesso aprovado (#1D4F9E → #001534,160°), centralizado em --g360-access-gradient; no celular, em que esse painel é oculto, a marca permanece no card de acesso. Footer e demais superfícies usam auto; impressão institucional é tratada no doc161.

## Fonte canônica e preservação do vetor

- Canônico: `apps/web/resources/brand/gabarito360-icon.svg`.
- Publicação: `apps/web/public/brand/gabarito360-icon.svg` e `gabarito360-icon-dark.svg`.
- PNG transparente para renderizadores: `apps/web/public/brand/gabarito360-icon.png` (1024×1024); versão dark também disponível.
- Origem oficial SHA256: `be2a95ed012f07c9fa6ab735af0739aa778fc54223ba5fd0b847890f8338fb0a`.
- XML normalizado em UTF8, sem DOCTYPE/metadados/namespaces do editor. O polygon foi convertido em path equivalente; geometria, ordem de pintura e traço foram preservados. Classes `g360-simbolo-check` e `g360-simbolo-circulo` permitem animação/CSS. Versão escura altera somente a cor da parte navy/traço para branco.

## Fontes e licenças

- Web: `public/fonts/brand/Figtree-ExtraBold.woff2` e `AlbertSans-ExtraBold.woff2`.
- PDFs/texto nativo: `apps/api/omr/fonts/Figtree-ExtraBold.ttf` e `AlbertSans-ExtraBold.ttf`.
- Instâncias estáticas peso800 dos arquivos variáveis oficiais Google Fonts, sem itálico nem pesos adicionais da marca.
- URLs primárias, commits fixados e hashes das origens: `resources/brand/fonts/sources.json`.
- Licenças OFL ao lado dos fontes públicos/API e em `resources/brand/fonts/`. Fontes anteriores Raleway e fontes do corpo não foram removidas.

## Assets publicados

Na raiz de `apps/web/public`: favicon.ico (16/32/48/64/128/256), favicon.svg adaptativo claro/escuro, favicon-16x16.png, favicon-32x32.png, favicon-48x48.png, favicon-96x96.png, apple-touch-icon.png180, android-chrome-192x192.png, android-chrome-512x512.png, icon-192.png, icon-512.png, maskable-icon-192.png e maskable-icon-512.png.

Os maskable têm fundo branco e todo o bounding box do símbolo dentro do círculo seguro de80% do canvas. `manifest.json` e `site.webmanifest` são equivalentes, com propósito any/maskable separado, tema navy e fundo `#f4f6fa`, coerente com a superfície existente. Não foi criado service worker ou promessa de funcionamento offline.

`public/brand/gabarito360-og.png` é1200×630, vetor e fontes reais, frase Correção de provas e relatórios educacionais. `layouts/_marca-head.blade.php` centraliza favicons, manifest, nome, metadados genéricos OG/Twitter e CSS da marca. Não publica nomes ou notas das telas privadas nos metadados sociais.

## Integrações

- Layout app (sidebar/header compacto), home, layout público e acesso.
- Menu e footer públicos; login, recuperação/redefinição, convites e seleção/troca de vínculo.
- Skeleton Blade e imagem do leitor/miniatura substituem o asset anterior pelo símbolo oficial, sem alterar filas ou ações.
- E-mail API: `resources/emails/base.html` usa PNG horizontal dark, gerado com as fontes corretas para clientes que não suportam SVG/fontes personalizadas. `EmailModelo` preenche somente a URL da marca com `config(emails.url_web)` escapada; remetentes, links de ação e conteúdo funcional permanecem.
- Configuração de nome: fallback Laravel substituído por Gabarito360 em web/API; APP_NAME explicitamente configurado continua tendo precedência.

## Geração e validação

Ambiente isolado com `scripts/branding/requirements.txt` (versões usadas fixadas). Executar na raiz:

```text
python scripts/branding/gerar_assets.py
python scripts/branding/verificar_assets.py
```

`--source-svg CAMINHO` reimporta o original; `--fontes` regenera TTF/WOFF2 a partir dos URLs/commits/hashes fixados. A geração normal é local, não consulta rede nem fontes do sistema. `resources/brand/assets-sha256.json` inventaria artefatos e hashes. Não usar imagegen ou substituir o símbolo por desenho aproximado.

No inventário, SVG/JSON/licenças têm hash canônico UTF8+LF, para não depender da conversão CRLF/LF do checkout Windows. PNG/ICO/fontes são conferidos byte a byte. Isso não se aplica à verificação de documentos emitidos, cujo hash continua sendo do arquivo PDF completo.

Verificações executadas pelo autor:52 validações de assets/fontes/licenças/hashes e círculo seguro; regeneração determinística com os mesmos hashes; Vite build concluído; verificador35scriptsBlade sem erros e sintaxe do leitor JS conferida. O build avisou apenas sobre otimização opcional de fallback Fontaine no scaffold existente. Dependências de geração foram instaladas fora do checkout; node_modules/build permanecem ignorados. `MarcaWebTest` cobre home/login mesmo sem API, renderização/acessibilidade do ícone e animação opt-in; execução PHP fica no ambiente equivalente do integrador. Testes de e-mail e capturas de console/mobile/tema/favicons também dependem dessa conferência integrada, não são declarados concluídos aqui.

## Inventário preservado e limites

- `public/images/g360-carregando.webp`: preservado por compatibilidade externa e evidências antigas; referências operacionais de branding foram substituídas. Não excluir sem auditoria de URLs externas.
- Regras antigas `.brand .logo` em app.css: preservadas para compatibilidade, sem uso pelos links substituídos. O autor desta integração não editou app.css, inclusive as regras de impressão `.ic` do integrador.
- Fontes Raleway/Instrument Sans/Figtree do corpo e assets de avatares não são logos e foram preservados.
- Templates welcome do scaffold Laravel foram mantidos; não são a página institucional Gabarito360 (`home/index`). Nenhuma rota foi alterada.
- Não havia manifest/PWA/OG oficial anterior no checkout. O favicon.ico foi atualizado no mesmo endereço, sem quebrar referências antigas ao caminho.
- Nenhum arquivo antigo foi apagado. PDFs/registros já emitidos e imagens de cartões não foram reescritos. Cadastro de escolas independentes/finanças permanece exclusivamente no planejamento EI1/doc163.

Publicação somente conforme autorização de homologação, após CI e revisão independente. Produção não autorizada por esta documentação.

Decisão posterior do responsável em05/11/2026: após comparar com o degradê antigo (#1D4F9E→#153B78), escolheu o novo (#1D4F9E→#001534), aplicado somente aos três painéis de acesso/recuperação/redefinição. A marca branca e os controles existentes permanecem.

## Página institucional e manutenção independentes (H1)

A página inicial aprovada mantém o degradê #1D4F9E→#001534 na abertura. Sua publicação estática também inclui a identidade oficial; a manutenção usa símbolo/wordmark oficiais e fontes locais, sem o badge antigo. O funcionamento servido pelo Nginx não exige PHP, API, sessão ou banco. O conteúdo de planos é conferido e congelado durante a exportação saudável; falha da API impede substituir a versão anterior.

Contrato único: `scripts/operacao/assets-publicos.php`, executável em PHP CLI sem inicializar Laravel. A whitelist contém apenas CSS compartilhado/da marca, app.js, fontes/licenças da marca, símbolo/OG, sprite já referenciado pelo CSS, favicons e manifests. Arquivos externos à lista e symlinks são recusados; o recibo verifica lista, SHA256 de cada arquivo, extras e integridade antes da ativação. O bundle tem limite de5MiB por arquivo. SVG/fontes/PNG não são recriados durante a publicação.

O identificador SHA256 deriva dos caminhos e bytes de entrada ordenados (texto UTF8+LF), mais versão explícita da transformação. O manifest é então reescrito para apontar para esse bundle; os hashes dos bytes finais ficam no recibo. Caminho público imutável: `/_institucional/marca-{sha256}/...`. Os caminhos relativos do CSS continuam válidos, inclusive `../fonts/brand/`. OG usa a origem pública absoluta informada pelo operador; links de navegação continuam relativos. Não se admite origem com credenciais, IP ou caminho. O namespace institucional desconhecido retorna404 sem cair no PHP.

Uso operacional, como root no servidor já autorizado:

```text
bash scripts/operacao/publicar-home.sh /srv/gabarito360/current /var/www/gabarito360-publico https://homologa.gabarito360.com.br
```

Os argumentos são raiz do projeto, raiz pública independente e origem pública. Preservados os padrões antigos dos dois primeiros; o terceiro deve ser explícito na homologação para não gerar OG apontando para produção. O publisher copia somente os três auxiliares fixos para um diretório temporário legível por www-data e executa a renderização anônima com esse usuário; o exportador PHP não exige acesso a diretórios de root. Não congela formulários, CSRF, tokens ou contexto autenticado.

Primeiro exporta/valida HTML e assets em versão nova; depois instala o bundle e só então troca atomicamente o symlink `current`. Falhas anteriores à troca mantêm a página publicada. Versões antigas e bundles antigos permanecem; o alias legado `/_institucional/{sha256}.css` continua funcionando. A volta à versão anterior utiliza seu pointer e os mesmos assets preservados. A instalação do snippet Nginx/rollback de configuração é responsabilidade do ensaio operacional e não é executada pelo exportador.

Validação local: lint dos dois PHP e sintaxe Bash passaram; dois testes puros passaram, cobrindo reprodução, fontes relativas, manifests/OG, whitelist, symlink, arquivo extra, adulteração e coexistência de versões. O caso de falha do publisher que exige root foi inicialmente pulado no WSL UID1000 e depois executado com sucesso em WSL root, mantendo o pointer anterior. A integração `PublicoNginxTest` foi ampliada para MIME, GET/HEAD de assets, manutenção503 e home/assets200 sem FastCGI, com Nginx isolado em porta/pid próprios. O integrador executou a suíte completa na VM120, em /tmp/h1-publico-teste, com quatro testes aprovados em2,247s e Nginx de porta/pid próprios: publisher com falha preservou o pointer, e home/assets permaneceram200 durante indisponibilidade do FastCGI fictício. Não houve alteração do Nginx global. Registro operacional privado informado: /root/g360-homologa/h1-ensaio-nginx/testes-publico.log. Isso valida a instância de ensaio; a pausa dos serviços reais de homologação permanece uma verificação operacional separada. Nenhum serviço global foi instalado, parado ou alterado pelo autor destes arquivos. Revisão visual independente da home/manutenção e CI continuam necessários antes da publicação somente em homologação.

Canal oficial informado pelo responsável: YouTube `https://www.youtube.com/@gabarito360`, incluído junto a Instagram/Facebook/TikTok no componente `rodape-publico`, com o mesmo formato textual, nome acessível e aviso de nova aba. A exportação estática reutiliza esse componente, sem outra lista de redes. O teste MarcaWebTest verifica os quatro URLs/rótulos e ausência de requisição externa; não consulta disponibilidade dos serviços.


## Apresentação pública sem preços (decisão posterior do responsável)

Home e `/planos` agora usam exclusivamente `config/apresentacao.php` e o partial `planos/_apresentacao`, sem consultar o catálogo comercial da API. Quatro públicos de atendimento são apresentados: Professor autônomo, Escolas, Núcleos e redes, Secretarias. São categorias de comunicação, sem criar perfis, contratos ou acesso novo. Não há valores, periodicidade, limites comerciais, compra ou promessa de implantação. Os recursos atuais ficam separados dos previstos no planejamento; editor, acervo e novas análises são identificados como futuros, sem prazo prometido.

Esta decisão substitui o requisito anterior de API de planos saudável durante a exportação: `exportar-home.php` renderiza a configuração pública local e recusa HTML com R$. A disponibilidade de API/preços não interfere na home nem na geração estática. Permanecem as verificações de assets, ausência de tokens e troca atômica do publisher. Planos existentes no banco, API comercial, checkout e regras financeiras não foram modificados. Revisão visual independente deve conferir home e página de modalidades, incluindo mobile e ajudas recolhidas, antes da integração.

Validação do incremento de apresentação: lint PHP passou em configuração/controllers/exportador/teste; PaginasPublicasTest e MarcaWebTest passaram em ambiente PHP8.3 isolado,11testes/97asserções. O ensaio não usou API real nem alterou o harness do navegador. Capturas atuais e parecer independente ficam com o integrador antes de publicar a homologação.


## Ampliação posterior da apresentação pública

O pedido detalhado de05/11 substitui a listagem pública de recursos futuros por integração na narrativa comercial, com selo literal **Em breve** em cada ocorrência das sete frentes. Manter as quatro modalidades, sem preços/periodicidade/limites/compra, e retirar o texto genérico de planos indefinidos. A contratação ainda não está aberta. A marca, o degradê e os ativos já aprovados permanecem. A nova página usa conteúdo local compartilhado entre home e /planos, demonstrações fictícias, menu móvel e FAQ acessíveis, sem implementar módulos futuros. Replanejamento e matriz no doc164; nova revisão/CI/publicação são exigidos para a versão final, sem reutilizar aprovação visual anterior como se cobrisse o novo conteúdo.

O contrato estático inclui explicitamente css/apresentacao-publica.css e js/apresentacao-publica.js. O segundo apenas melhora o fechamento do menu; conteúdo e navegação básica continuam emHTML. Whitelist fechada, SHA, MIME e preservação de versões permanecem obrigatórios. Não incluir novos assets de demonstração sem caminho individual conferido no contrato.
