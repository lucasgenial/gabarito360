import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const contexto={window:{}};vm.runInNewContext(fs.readFileSync(new URL('../../apps/web/public/js/preparo-foto-geometria.js',import.meta.url),'utf8'),contexto);const g=contexto.window.G360FotoGeometria;
function contem(m,w,h,d){const [a,b,c,e,x,y]=m,det=a*e-b*c,cx=w/2+x,cy=h/2+y;for(const px of [0,d])for(const py of [0,d]){const u=(e*(px-cx)-c*(py-cy))/det,v=(-b*(px-cx)+a*(py-cy))/det;assert.ok(Math.abs(u)<=w/2+1e-6,`canto x ${u}`);assert.ok(Math.abs(v)<=h/2+1e-6,`canto y ${v}`);}}
test('foto rotacionada e deslocada cobre todos os cantos do recorte',()=>{
 for(const d of [200,220,280])for(const [w,h] of [[1200,1600],[1600,800],[384,384]])for(let ang=-180;ang<=180;ang+=7.5){const r=ang*Math.PI/180;for(const escala of [0.01,0.2,1,4]){const m=[Math.cos(r)*escala,Math.sin(r)*escala,-Math.sin(r)*escala,Math.cos(r)*escala,-400,200];const n=g.ajustar(m,w,h,d,d);contem(n,w,h,d);assert.ok(Math.hypot(n[0],n[1])<=g.escalaMinima(n,w,h,d,d)*3+1e-9);}}
});
test('correção conserva enquadramento já válido e é idempotente',()=>{
 const m=[0.4,0,0,0.4,110-600,110-800],n=g.ajustar(m,1200,1600,220,220);
 n.forEach((v,i)=>assert.ok(Math.abs(v-m[i])<1e-9));const p=g.ajustar(n,1200,1600,220,220);p.forEach((v,i)=>assert.ok(Math.abs(v-n[i])<1e-9));
});
test('matriz inválida não chega à exportação',()=>{assert.throws(()=>g.ajustar([NaN,0,0,1,0,0],100,100,220,220));assert.throws(()=>g.ajustar([0,0,0,0,0,0],100,100,220,220));});
