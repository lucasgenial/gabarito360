# F3 — arquivos e cache técnico: planejamento de entrada

**Data:** 09/11/2026. **Status:** planejamento de entrada revisado com os resultados de F1/F2; execução autorizada pela regra de etapas de [194](194-planejamento-desempenho-integral.md). **Escopo:** somente homologação. Produção fora do escopo.

## Ponto de partida

Base `56f2018` (main após PR 232), com a F2 publicada na homologação. A medição F1 ([196](196-diagnostico-desempenho-f1.md)) encontrou na turma 78 recursos e 8.161.093 bytes transferidos, com cache normal praticamente igual ao cache desabilitado. As causas confirmadas no código e no servidor são as seguintes:

| Achado | Evidência | Efeito |
|---|---|---|
| `Cache-Control: no-store` em todo o servidor web da homologação | `add_header` no nível `server` do site nginx da VM 120 | Nenhum asset é reaproveitado entre navegações |
| Sprites de avatares em PNG RGB 1254 × 1254 | `estudantes-v1.png` 2.456.765 B, `educadores-v1.png` 2.293.282 B, `brasoes-v1.png` 2.276.398 B | 7.026.445 B só de sprites na turma |
| Dois runtimes ECharts na turma | `echarts-6.1.0.common.min.js` (715.020 B) pelo `_modelos` e `turma-graficos.js` (565.705 B) com ECharts embutido pelo esbuild | Download e execução duplicados |
| Versionamento inconsistente | parte das views usa `?v=filemtime`, parte usa `asset()` sem versão; `filemtime` muda a cada release, mesmo sem mudança de conteúdo | Sem base segura para cache imutável |
| Manifesto PWA com 401 | `<link rel="manifest">` é buscado sem credenciais e a Basic Auth recusa | Erros repetidos no console e na borda |

As sprites foram geradas como imagens, não como pixel art em grade: têm entre 281 mil e 371 mil cores e transições suaves. Em WebP com qualidade 90 e a mesma resolução, ficam com cerca de 419 KB, 363 KB e 356 KB.

## Decisões

1. **Versão por conteúdo.** Um helper `ativo('caminho')` gera `asset(caminho)?v=<hash>`, com xxh128 do arquivo truncado em 16 hexadecimais e memorizado por requisição. Todas as referências da web a CSS e JS locais passam a usar o helper. Arquivo ausente mantém a URL sem versão, sem quebrar a tela.
2. **Política de cache por classe.** No nginx da homologação, arquivos estáticos (`css`, `js`, `woff2`, `webp`, `png`, `svg`, `ico`) com `v` no formato do helper recebem `Cache-Control: private, max-age=31536000, immutable`. Estáticos sem versão recebem `private, no-cache` (revalidação por ETag). HTML, JSON, PHP, API, fotos e documentos protegidos continuam `no-store`. `private` impede que a borda compartilhe conteúdo protegido pela Basic Auth.
3. **Proteção preservada.** A Basic Auth, o `X-Robots-Tag` e o bloqueio de `/storage` continuam em todas as respostas. O manifesto recebe `crossorigin="use-credentials"` para ser buscado com a credencial do ambiente, sem abrir exceção na proteção.
4. **Sprites em WebP.** Novos arquivos `*-v2.webp` na mesma resolução 1254 × 1254, qualidade 90. O CSS passa a apontar para eles. Os PNG v1 permanecem no diretório para a home independente já exportada e são removidos numa entrega posterior. A troca exige revisão visual de UI/UX em claro, escuro, desktop e celular.
5. **Um runtime de gráficos por página.** `scripts/graficos/build.mjs` gera `js/libs/echarts-g360.min.js` (569.698 B), com barras, pizza, grade, tooltip, legenda, título, aria, `LabelLayout` e renderização SVG, exposto como `window.echarts`. O `turma-graficos.js` deixa de embutir o ECharts (565.705 → 9.955 B) e usa esse runtime. `graficos.js` também passa a usá-lo no lugar do `echarts-6.1.0.common.min.js` (715.020 B). O partial `layouts/_echarts` usa `@once` para não carregar duas cópias. Sem o runtime, a turma mostra um estado vazio com texto em vez de falhar. O `common` antigo fica no diretório apenas para a prévia local do editor e sai numa limpeza posterior.
6. **Bibliotecas sob demanda.** `heic-to` (3,2 MB), pdf.js e pdf-lib continuam carregados só nos fluxos que precisam deles. Verificar na implementação que nenhuma página comum os carrega.
7. **Sem cache de dados.** Nenhum dado escolar, título livre, credencial ou decisão de permissão entra em cache do navegador ou do servidor. O cache estrutural do editor (PR 227) é conferido sem mudança de comportamento.

## Subdivisões

| Parte | Entrega | Critério |
|---|---|---|
| F3-A | Helper `ativo()` e migração de todas as referências | Nenhuma referência local de CSS/JS sem versão nas views; testes do helper |
| F3-B | Sprites WebP e CSS atualizado | Bytes medidos; revisão visual aprovada por UI/UX |
| F3-C | Runtime único de ECharts na turma | Gráficos da turma idênticos; um único ECharts na rede |
| F3-D | Política de cache no nginx da homologação e manifesto com credencial | Segunda navegação reaproveita assets; nova release baixa os novos bytes; HTML continua `no-store` |
| F3-E | Medição comparável e fechamento | Mesmos percursos da F1 (painel, lista, turma) frio e quente; resultados registrados neste documento |

## Validação e publicação

Testes PHP do helper, testes JS existentes, CI completa e revisão independente de segurança/arquitetura. A configuração nginx fica versionada no repositório como referência e é aplicada só na VM 120, com backup do arquivo anterior e `nginx -t` antes do reload. Publicação somente na homologação, pelo roteiro de [127](127-h1-homologacao-isolada.md) e [129](129-h1-publicacao-ci1-e.md), com backup, comparação de dados e plano de retorno. Produção continua dependendo de decisão expressa do responsável.

## Resultado na homologação

Publicada em 09/11/2026 a release `9aee7fa21534aeab2a79fdaebd63e65bb61cdcff` (PR 233), fase `HOMOLOGACAO-PERF-F3`, sobre `a7e08f5`. O operador manteve a homologação em manutenção até conferir os dados. Confirmou 130 tabelas e 201 arquivos idênticos, sem migration e sem mudança de esquema, com backup SQL e de arquivos validado antes da troca. Reutilizou o `vendor` com locks idênticos e preservou a home independente, o isolamento FPM e o túnel.

Depois da release, o site nginx da VM recebeu o `map` e o snippet de `infra/homologacao/nginx`, com backup do arquivo anterior e `nginx -t`. O hash do site passou de `f5096d6c…` para `aa1d9c0d…`, e os próximos operadores precisam usar o hash novo como referência. Na conferência por loopback:

| Resposta | Cache-Control |
|---|---|
| CSS/JS com `?v=` de 16 hexadecimais | `private, max-age=31536000, immutable` |
| Estáticos sem versão ou com versão inválida, inclusive sprites | `private, no-cache` (ETag; revalidação devolve 304 sem corpo) |
| `/login`, manifesto, `/.env` (403), `/storage` (404) | `no-store` |
| Asset sem Basic Auth | 401 |

Uma URL com `v` forjado no formato válido também recebe cache imutável, mas só no cache privado de quem a pediu. Ela continua servindo o arquivo atual, sem expor conteúdo.

Medição no Chrome autenticado do coordenador, nos mesmos três percursos da F1. A coluna "com cache" é a segunda navegação. A coluna "assets sem cache" baixa de novo todos os CSS, JS, imagens e fontes da tela com URL única, o equivalente ao cache desabilitado restrito a arquivos técnicos. Ela não inclui documento HTML nem chamadas BFF.

| Percurso | F1: bytes transferidos, cache normal | F3: bytes transferidos, com cache | F3: assets sem cache |
|---|---:|---:|---:|
| Painel | 2.609.068 | 15.693 | 516.733 |
| Lista de turmas | 5.270.691 | 53.324 | 1.244.323 |
| Turma (pela lista) | 8.159.322 | 39.688 | 1.695.228 |

Na turma, os quatro gráficos renderizam com um único runtime (`echarts-g360`, 205.826 B transferidos com compressão) e sem erros no console. Os avatares e brasões aparecem no tema claro e no escuro. Abrir `/app/turmas/detalhes` direto, sem vir da lista, mostra "Não foi possível abrir esta tela", comportamento da UR4 que já existia, porque a URL não carrega identificador.

Limites: amostra única por percurso, um perfil (coordenador), desktop e rede real pela borda. Não foram medidos LCP/INP nem celular real. A conferência de 36–44 px dos brasões foi feita nas comparações estáticas da revisão de design. Os PNG v1 e o `echarts-6.1.0.common.min.js` continuam no diretório para a home exportada e a prévia local.
