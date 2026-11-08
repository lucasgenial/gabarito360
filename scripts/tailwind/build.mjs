import {spawnSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';
import {readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import {createHash} from 'node:crypto';
import {build} from 'esbuild';

const caminho = valor => fileURLToPath(new URL(valor,import.meta.url));
const origem = caminho('../../apps/web/resources/previas/tailwind/');
const destino = caminho('../../apps/web/public/previas/tailwind/');
mkdirSync(destino,{recursive:true});
const resultado = spawnSync(process.execPath,[caminho('node_modules/@tailwindcss/cli/dist/index.mjs'),
  '-i',origem+'interface.css','-o',destino+'interface.css','--minify'],{cwd:caminho('.'),stdio:'inherit'});
if(resultado.status!==0)process.exit(resultado.status??1);
const normalizar = texto => texto.replace(/\r\n/g,'\n').trimEnd()+'\n';
const css = normalizar(readFileSync(destino+'interface.css','utf8'));
const avisoFormValidation=readFileSync(caminho('node_modules/@form-validation/core/lib/index.js'),'utf8').match(/^\/\*\*[\s\S]*?\*\//)?.[0];
if(!avisoFormValidation)throw new Error('Aviso de autoria do FormValidation ausente.');
const pacote = await build({entryPoints:[origem+'interface.js'],nodePaths:[caminho('node_modules')],bundle:true,write:false,format:'iife',platform:'browser',target:'es2022',minify:true,legalComments:'inline',banner:{js:avisoFormValidation},define:{'process.env.NODE_ENV':'"production"'}});
// Preservar os avisos legais, removendo somente espaços finais dos comentários.
const js = normalizar(pacote.outputFiles[0].text.replace(/\/\*[\s\S]*?\*\//g,aviso=>aviso.replace(/[ \t]+$/gm,'')));
const hash = texto => createHash('sha256').update(texto).digest('hex').slice(0,16);
writeFileSync(destino+'MATERIAL-SYMBOLS-LICENSE.txt',normalizar(readFileSync(origem+'MATERIAL-SYMBOLS-LICENSE.txt','utf8')));
writeFileSync(destino+'interface.css',css);
writeFileSync(destino+'interface.js',js);
writeFileSync(destino+'index.html',normalizar(readFileSync(origem+'index.html','utf8'))
  .replaceAll('__CSS_VERSION__',hash(css)).replaceAll('__JS_VERSION__',hash(js)));
console.log('Prévia isolada gerada com versões de conteúdo.');
