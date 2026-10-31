// Mede as telas geradas por rodar.sh em 6 larguras: rolagem lateral, erro de JavaScript e excesso de imagens.
// Uso: node medir.mjs <pasta com os .html> <raiz do repositório>
import { readFileSync, readdirSync } from 'node:fs';
import { join } from 'node:path';
import { chromium } from 'playwright-core';

const [pasta, raiz] = process.argv.slice(2);
const LARGURAS = [375, 768, 1024, 1440, 1920, 3840];
const LIMITE_IMAGENS = 12; // pedidos de foto ao abrir a tela; cada um ocupa dois processos PHP
const BASE = 'http://auditoria.local';

// Lista fictícia: 47 cartões com foto, como a turma que derrubou o site em 30/10/2026
const CARTOES = Array.from({ length: 47 }, (_, i) => ({
  id: i + 1, aluno_id: i + 1, aluno_nome: `Aluno ${i + 1}`, turma: '3A', matricula: String(i + 1),
  lingua: 'ING', status: 'lido', ambiguas: 0, nota_final: 5, acertos: 20, total_questoes: 45, tem_imagem: true,
  miniatura_url: `/api/v1/cartoes/${i + 1}/arquivo/miniatura?signature=teste`,
}));
const STATUS = { total: 47, lidos: 47, pendentes: 0, ambiguos: 0, concluida: false, esperados: 54,
  ambiguidades: [], recentes: [], prova_status: 'em_correcao', correcao: {} };

const navegador = await chromium.launch({ channel: 'chrome', headless: true });
const paginas = readdirSync(pasta).filter((f) => f.endsWith('.html')).sort();
const falhas = [];

for (const largura of LARGURAS) {
  const contexto = await navegador.newContext({ viewport: { width: largura, height: 900 } });
  await contexto.route('**/*', (rota) => {
    const url = new URL(rota.request().url());
    if (url.origin === BASE && url.pathname.endsWith('.html')) {
      return rota.fulfill({ contentType: 'text/html', body: readFileSync(join(pasta, url.pathname.slice(1))) });
    }
    if (url.pathname.endsWith('/css/app.css')) return rota.fulfill({ path: join(raiz, 'apps/web/public/css/app.css') });
    if (url.pathname.endsWith('/js/app.js')) return rota.fulfill({ path: join(raiz, 'apps/web/public/js/app.js') });
    if (url.pathname.includes('/js/vendor/') || url.pathname.endsWith('/js/graficos.js')) return rota.fulfill({ path: join(raiz, 'apps/web/public', url.pathname.slice(url.pathname.indexOf('/js/'))) });
    if (url.pathname.endsWith('/acompanhar/cartoes')) return rota.fulfill({ json: { cartoes: CARTOES } });
    if (url.pathname.endsWith('/acompanhar/status')) return rota.fulfill({ json: STATUS });
    return rota.abort();
  });
  for (const arquivo of paginas) {
    const pagina = await contexto.newPage();
    const erros = [];
    let imagens = 0;
    pagina.on('pageerror', (e) => erros.push(e.message.split('\n')[0]));
    pagina.on('request', (r) => { if (/\/cartoes\/\d+\/imagem/.test(r.url())) imagens += 1; });
    await pagina.goto(`${BASE}/${arquivo}`, { waitUntil: 'load' });
    await pagina.waitForTimeout(150);
    const sobra = await pagina.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    if (sobra > 1) falhas.push(`${arquivo} @${largura}: rolagem lateral de ${sobra}px`);
    if (imagens > LIMITE_IMAGENS) falhas.push(`${arquivo} @${largura}: ${imagens} fotos pedidas ao abrir`);
    erros.forEach((e) => falhas.push(`${arquivo} @${largura}: erro de JavaScript: ${e}`));
    await pagina.close();
  }
  await contexto.close();
}
await navegador.close();

falhas.forEach((f) => console.log(f));
console.log(`telas: ${paginas.length} larguras: ${LARGURAS.length} falhas: ${falhas.length}`);
process.exit(falhas.length ? 1 : 0);
