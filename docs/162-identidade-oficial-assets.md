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
- Login/recuperação usam versão dark explicitamente sobre painel navy; no celular, em que esse painel é oculto, a marca permanece no card de acesso. Footer e demais superfícies usam auto; impressão institucional é tratada no doc161.

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
