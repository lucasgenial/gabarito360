import {build} from 'esbuild';
import {fileURLToPath} from 'node:url';
import {mkdirSync,copyFileSync} from 'node:fs';
const arquivo=p=>fileURLToPath(new URL(p,import.meta.url));
await build({entryPoints:[arquivo('../../apps/web/resources/js/notificacoes.js')],bundle:true,minify:true,format:'iife',platform:'browser',conditions:['browser'],nodePaths:[arquivo('./node_modules')],outfile:arquivo('../../apps/web/public/js/notificacoes.js'),legalComments:'eof'});
for(const pacote of ['solid-toast','solid-js']){
 const destino=arquivo('../../apps/web/public/js/libs/'+pacote+'/');mkdirSync(destino,{recursive:true});
 copyFileSync(arquivo('./node_modules/'+pacote+'/LICENSE'),destino+'LICENSE');
}
