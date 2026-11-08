# GridStack dos painéis

Versões fixadas: GridStack 14.1.0 (MIT) e esbuild 0.28.2. Bundle local, sem CDN ou dependência nova.

```sh
npm ci --ignore-scripts --prefix scripts/paineis-grid
node --test scripts/tests/painel-grade.test.mjs
npm run build --prefix scripts/paineis-grid
```

Fontes atuais: `apps/web/resources/js/painel-grade.js`, `painel-geometria.js` e `resources/css/painel-grade.css`. O build gera `public/js/painel-grade.js`, `public/css/painel-grade.css` e copia a licença para `public/js/libs/gridstack`. `painel-linhas.js` e seu teste permanecem como histórico da versão anterior; não são importados nem executados pelo cliente atual.

Cada seção autorizada possui uma instância GridStack nativa em modo `compact`. A biblioteca executa drag, resize, deslocamentos dos vizinhos e reaproveitamento de espaços; pode reordenar cards para preencher lacunas. Não há algoritmo de linhas, expansão das larguras ou altura uniforme. Todo card tem altura própria. O resumo fixo permanece fora das instâncias e do payload. Não há criação, exclusão, duplicação, subgrids, aceitação externa nem troca entre seções.

O helper usa a classe `GridStackEngine` real para canonicalizar em memória o mapa recebido e completar somente IDs autorizados que faltam. A entrada é validada antes de ir à biblioteca: catálogo, seção, IDs únicos, até 64 cards, 12 colunas e 200 linhas, limites e colisões. A projeção de volta valida o conjunto inteiro e as âncoras; qualquer falha restaura a disposição aceita anterior. Não envia `grid.save()` bruto, conteúdo, HTML ou objetos DOM.

Cards `fixo:true` usam `locked`, `noMove` e `noResize`. A aplicação também bloqueia comandos diretos e compara as caixas fixadas com o snapshot anterior: a engine nativa permite chamadas diretas em nós locked e não substitui essa guarda. O teclado envia coordenadas à API `grid.update()`; Shift altera largura/altura. A compactação continua nativa. Toda leitura da geometria preserva os campos opcionais conhecidos e atualiza `altura_desejada=h` somente quando o card efetivamente sofre resize.

Mapas antigos válidos são compactados na primeira apresentação, sem gravar automaticamente o JSON da API. O snapshot de Cancelar é essa apresentação inicial já compactada. Salvar é explícito e envia a disposição compacta atual via `G360Requisicao`, com versão/chave/contexto/CSRF existentes. Cancelar restaura o snapshot inteiro e a apresentação Tabela/Cards; reabrir aplica a mesma canonicalização idempotente. `altura_livre` e `altura_desejada` ausentes não são injetados ao carregar; quando presentes, continuam no transporte legado, sem regra de igualar alturas ou controle de altura da linha.

Organização disponível somente em viewport CSS de pelo menos 1024×700 (não mede polegadas físicas). Abaixo desse limite não consulta nem salva preferências, desmonta as instâncias e volta à composição fluida sem sobrescrever o desktop. Retornar consulta o estado atual. Epoch, abort, 409, bloqueio de contexto antigo e ausência de replay automático permanecem. H/V e Tabela/Cards continuam com estado dirty e salvamento explícito no desktop; no mobile a apresentação é temporária.

O teste Node compila em memória a engine 14.1.0 instalada e o helper com esbuild, sem mocks de posicionamento e sem gerar bundle público. Cobre encaixe de alturas diferentes, gaps, pins, rejeição de 201 linhas, IDs/perfis/seções, formas legadas, completar componentes e SaveReload/Cancelar. Testes de engine não substituem a revisão independente no Chrome, incluindo desktop/mobile, ponteiro, teclado e requisições de preferências.

Referências primárias: [modos da versão14.1.0](https://github.com/gridstack/gridstack.js/blob/v14.1.0/src/types.ts), [engine e compactação](https://github.com/gridstack/gridstack.js/blob/v14.1.0/src/gridstack-engine.ts), [API GridStack](https://gridstackjs.com/doc/html/classes/GridStack.html). Controles visuais mantêm o padrão do projeto e exigem aprovação independente de UI/UX.
