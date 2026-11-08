export function baseResultados(validos,total,excluidos){
 if(![validos,total,excluidos].every(v=>Number.isSafeInteger(v)&&v>=0)||validos+excluidos!==total)return 'A base completa e as exclusões não foram informadas nesta consulta.';
 return `Base: ${validos} de ${total} resultados; ${excluidos} sem percentual válido.`;
}

export function posicaoAjuda(alvo,caixa,viewport){
 const margem=16;
 const left=Math.max(margem,Math.min(alvo.left,viewport.width-caixa.width-margem));
 const abaixo=alvo.bottom+8;
 const top=abaixo+caixa.height<=viewport.height-margem?abaixo:Math.max(margem,alvo.top-caixa.height-8);
 return {left,top};
}
