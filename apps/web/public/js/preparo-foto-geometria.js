(function(root){
 'use strict';
 function escalaMinima(m,w,h,vw,vh){var a=Math.atan2(m[1],m[0]),c=Math.abs(Math.cos(a)),s=Math.abs(Math.sin(a));return Math.max((c*vw+s*vh)/w,(s*vw+c*vh)/h);}
 function ajustar(m,w,h,vw,vh){
  if(!m.every(Number.isFinite)||Math.min(w,h,vw,vh)<=0)throw new Error('Enquadramento inválido.');
  var antigo=Math.hypot(m[0],m[1]);if(antigo<=0)throw new Error('Enquadramento inválido.');
  var a=Math.atan2(m[1],m[0]),c=Math.cos(a),s=Math.sin(a),min=escalaMinima(m,w,h,vw,vh),escala=Math.max(min,Math.min(min*3,antigo)),r=escala/antigo;
  var qx=vw/2,qy=vh/2,cx=qx-r*(qx-(w/2+m[4])),cy=qy-r*(qy-(h/2+m[5]));
  var u=(c*(qx-cx)+s*(qy-cy))/escala,v=(-s*(qx-cx)+c*(qy-cy))/escala;
  var ex=(Math.abs(c)*vw+Math.abs(s)*vh)/(2*escala),ey=(Math.abs(s)*vw+Math.abs(c)*vh)/(2*escala);
  u=Math.max(-w/2+ex,Math.min(w/2-ex,u));v=Math.max(-h/2+ey,Math.min(h/2-ey,v));
  cx=qx-escala*(c*u-s*v);cy=qy-escala*(s*u+c*v);
  return [escala*c,escala*s,-escala*s,escala*c,cx-w/2,cy-h/2];
 }
 var api={escalaMinima:escalaMinima,ajustar:ajustar};
 if(typeof module==='object'&&module.exports)module.exports=api;else root.G360FotoGeometria=api;
})(typeof window!=='undefined'?window:globalThis);
