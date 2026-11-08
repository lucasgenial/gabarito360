import test from 'node:test';
import assert from 'node:assert/strict';
import {baseResultados,posicaoAjuda} from '../../apps/web/resources/js/painel-indicadores.js';

test('zero confirmado, exclusões e ausência de base são estados diferentes',()=>{
 assert.equal(baseResultados(0,0,0),'Base: 0 de 0 resultados; 0 sem percentual válido.');
 assert.equal(baseResultados(2,3,1),'Base: 2 de 3 resultados; 1 sem percentual válido.');
 for(const v of [[null,0,0],[0,undefined,0],['2',3,1],[3,2,0],[-1,1,2]])assert.match(baseResultados(...v),/não foram informadas/);
});
test('ajuda cabe no celular e inverte para cima perto do rodapé',()=>{
 assert.deepEqual(posicaoAjuda({left:280,top:700,bottom:744},{width:288,height:240},{width:320,height:844}),{left:16,top:452});
 assert.deepEqual(posicaoAjuda({left:50,top:100,bottom:144},{width:320,height:240},{width:1440,height:900}),{left:50,top:152});
});
