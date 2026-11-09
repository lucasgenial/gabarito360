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
