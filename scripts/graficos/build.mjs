import {build} from 'esbuild';
import {fileURLToPath} from 'node:url';
import {mkdirSync,copyFileSync,readFileSync,writeFileSync} from 'node:fs';
const arquivo=p=>fileURLToPath(new URL(p,import.meta.url));
const semEspacos=p=>writeFileSync(p,readFileSync(p,'utf8').replace(/[ \t]+$/gm,''));

// Runtime único (F3, docs/198): window.echarts com barras, pizza, aria e SVG.
const runtime=arquivo('../../apps/web/public/js/libs/echarts-g360.min.js');
await build({entryPoints:[arquivo('./runtime.js')],bundle:true,minify:true,format:'iife',globalName:'echarts',platform:'browser',nodePaths:[arquivo('./node_modules')],outfile:runtime,legalComments:'eof'});
semEspacos(runtime);

// Gráficos da turma sem ECharts embutido: usam o runtime acima.
const bundle=arquivo('../../apps/web/public/js/turma-graficos.js');
await build({entryPoints:[arquivo('../../apps/web/resources/js/turma-graficos.js')],bundle:true,minify:true,format:'iife',platform:'browser',nodePaths:[arquivo('./node_modules')],outfile:bundle,legalComments:'eof'});
semEspacos(bundle);

const destino=arquivo('../../apps/web/public/js/libs/echarts/');mkdirSync(destino,{recursive:true});
for(const nome of ['LICENSE','NOTICE'])copyFileSync(arquivo('./node_modules/echarts/'+nome),destino+nome);
