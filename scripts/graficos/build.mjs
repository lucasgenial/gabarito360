import {build} from 'esbuild';
import {fileURLToPath} from 'node:url';
import {mkdirSync,copyFileSync,readFileSync,writeFileSync} from 'node:fs';
const arquivo=p=>fileURLToPath(new URL(p,import.meta.url));
await build({entryPoints:[arquivo('../../apps/web/resources/js/turma-graficos.js')],bundle:true,minify:true,format:'iife',platform:'browser',nodePaths:[arquivo('./node_modules')],outfile:arquivo('../../apps/web/public/js/turma-graficos.js'),legalComments:'eof'});
const destino=arquivo('../../apps/web/public/js/libs/echarts/');mkdirSync(destino,{recursive:true});
for(const nome of ['LICENSE','NOTICE'])copyFileSync(arquivo('./node_modules/echarts/'+nome),destino+nome);

const bundle=arquivo('../../apps/web/public/js/turma-graficos.js');
writeFileSync(bundle,readFileSync(bundle,'utf8').replace(/[ \t]+$/gm,''));
