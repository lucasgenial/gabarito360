import{build}from'esbuild';import{fileURLToPath}from'node:url';import{readFileSync,writeFileSync,mkdirSync,copyFileSync}from'node:fs';
const path=p=>fileURLToPath(new URL(p,import.meta.url));
await build({entryPoints:[path('../../apps/web/resources/js/painel-grade.js')],bundle:true,minify:true,format:'iife',platform:'browser',nodePaths:[path('./node_modules')],outfile:path('../../apps/web/public/js/painel-grade.js'),legalComments:'eof'});
writeFileSync(path('../../apps/web/public/css/painel-grade.css'),(readFileSync(path('./node_modules/gridstack/dist/gridstack.css'),'utf8')+'\n'+readFileSync(path('../../apps/web/resources/css/painel-grade.css'),'utf8')).replace(/\r\n/g,'\n'));
const dest=path('../../apps/web/public/js/libs/gridstack/');mkdirSync(dest,{recursive:true});copyFileSync(path('./node_modules/gridstack/LICENSE'),dest+'LICENSE');
const bundle=path('../../apps/web/public/js/painel-grade.js');writeFileSync(bundle,readFileSync(bundle,'utf8').replace(/[ \t]+$/gm,''));
