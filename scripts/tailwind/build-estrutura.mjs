import {readFileSync,writeFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import {spawnSync} from 'node:child_process';
import postcss from 'postcss';

const caminho=p=>fileURLToPath(new URL(p,import.meta.url));
const css=postcss.parse(readFileSync(caminho('../../apps/web/public/css/app.css'),'utf8'));
// O CSS original continua disponível para público/auth. A área autenticada recebe
// somente as famílias ainda não migradas, sem regras concorrentes da estrutura.
const estrutura=/\.(?:app-(?:header|casca|corpo|lateral|conteudo|footer)|lateral-(?:menu|rotulo|recolher)|header-right|user-menu(?:-[a-z]+)*|nav-(?:inferior(?:-[a-z]+)*|folha(?:-[a-z]+)*)|breadcrumb(?:-dinamico)?|g-pagina(?:-[a-z]+)*|page-title|page-sub|eyebrow|contexto-(?:ativo|resumo(?:-titulo)?|dados|conteudo|erro|trocar))(?=[\s.#:[>+~),]|$)/;
const representativa=/\.(?:btn(?:-[a-z]+)*|card(?:-pad)?|field(?:-help)?|input|select|modal(?:-head|-body|-foot|-close|-backdrop)?)(?=[\s.#:[>+~),]|$)/;
css.walkRules(regra=>{
  for(let pai=regra.parent;pai;pai=pai.parent)if(pai.type==='atrule'&&pai.name==='media'&&/\bprint\b/.test(pai.params))return;
  const manter=regra.selectors.filter(seletor=>!estrutura.test(seletor));
  if(!manter.length)regra.remove();else regra.selectors=manter.map(s=>representativa.test(s)?s.replace(/(::[a-z-]+(?:\([^)]*\))?)?$/i,':not(:where(.tela-turmas,.tela-turmas *,.tela-turma,.tela-turma *))$1'):s);
});
css.walkAtRules('media',regra=>{if(!regra.nodes?.length)regra.remove();});
const normalizar=s=>s.replace(/\r\n/g,'\n').trimEnd()+'\n';
writeFileSync(caminho('../../apps/web/public/css/app-conteudo-legado.css'),normalizar(css.toString()));
const exec=spawnSync(process.execPath,[caminho('node_modules/@tailwindcss/cli/dist/index.mjs'),'-i',caminho('../../apps/web/resources/css/estrutura-app.css'),'-o',caminho('../../apps/web/public/css/estrutura-app.css'),'--minify'],{stdio:'inherit'});
if(exec.status!==0)throw new Error('Falha ao compilar a estrutura Tailwind.');
const saida=caminho('../../apps/web/public/css/estrutura-app.css');
writeFileSync(saida,normalizar(readFileSync(saida,'utf8')));
console.log('Estrutura autenticada compilada; legado do shell retirado somente da saída operacional.');
