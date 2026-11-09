import {createServer} from 'node:http';import {readFile,stat,realpath} from 'node:fs/promises';import {resolve,extname,sep} from 'node:path';import {fileURLToPath} from 'node:url';
const root=fileURLToPath(new URL('.',import.meta.url)),pub=resolve(root,'../../apps/web/public');
const mime={'.html':'text/html; charset=utf-8','.js':'text/javascript','.css':'text/css','.svg':'image/svg+xml','.png':'image/png','.woff2':'font/woff2','.ico':'image/x-icon','.txt':'text/plain'};
const port=Number(process.env.G360_PREVIA_PORT||9020);
createServer(async(req,res)=>{try{
 if(req.method!=='GET'&&req.method!=='HEAD'){res.writeHead(405).end();return;}
 const url=new URL(req.url,'http://localhost'),p=decodeURIComponent(url.pathname);if(p.includes('\\')||p.includes('\0')||p.split('/').some(s=>s==='.'||s==='..'))throw Error();let file,allowed;
 if(p==='/'){file=resolve(root,'index.html');allowed=file;}else if(p.startsWith('/dist/')){file=resolve(root,'.'+p);allowed=resolve(root,'dist')+sep;}
 else if(/^\/(brand\/|fonts\/brand\/|images\/avatares\/|js\/libs\/echarts-6\.1\.0\.common\.min\.js$|favicon\.ico$)/.test(p)){file=resolve(pub,'.'+p);allowed=p.startsWith('/brand/')?resolve(pub,'brand')+sep:p.startsWith('/fonts/brand/')?resolve(pub,'fonts/brand')+sep:p.startsWith('/images/avatares/')?resolve(pub,'images/avatares')+sep:file;}else throw Error();
 file=await realpath(file);if(allowed.endsWith(sep)?!file.startsWith(allowed):file!==allowed)throw Error();
 if(!(await stat(file)).isFile()||!mime[extname(file)])throw Error();
 res.writeHead(200,{'Content-Type':mime[extname(file)],'Cache-Control':'no-store','X-Content-Type-Options':'nosniff','Referrer-Policy':'no-referrer','Content-Security-Policy':"default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:; connect-src 'none'; object-src 'none'; frame-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'"});res.end(req.method==='HEAD'?undefined:await readFile(file));
 }catch{res.writeHead(404).end('Não encontrado');}
}).listen(port,'127.0.0.1',()=>console.log('Prévia local em http://localhost:'+port));
