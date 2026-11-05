import {readFileSync} from 'node:fs';
export const imagemTeste = '<svg xmlns="http://www.w3.org/2000/svg" width="1025" height="1600" viewBox="0 0 1025 1600"><rect width="1025" height="1600" fill="white"/><g fill="#123566" font-family="sans-serif"><text x="70" y="120" font-size="44">Cartão-resposta de teste</text><text x="70" y="195" font-size="30">Dados fictícios para conferência</text><text x="70" y="1480" font-size="30">Final do cartão — imagem inteira</text></g><g fill="none" stroke="#123566" stroke-width="5"><rect x="70" y="270" width="885" height="1100"/><circle cx="200" cy="400" r="35"/><circle cx="350" cy="400" r="35"/><circle cx="500" cy="400" r="35"/><circle cx="650" cy="400" r="35"/><circle cx="800" cy="400" r="35"/></g><circle cx="500" cy="400" r="25" fill="#123566"/></svg>';
export const itensTeste = [65,66].map(id=>({cartao_id:id,status:'lido',aluno_nome:'Aluno fictício '+id,turma:'Turma de teste',numero_questao:1,alternativa:'C',ambigua:false,detectadas:['C'],foto_url:`/api/v1/cartoes/${id}/arquivo/original?acesso=ficticio%20teste&expires=9999999999&signature=assinatura-ficticia`,endireitada_url:'/recorte.svg',recorte:{x:.05,y:.195,largura:.9,altura:.12}}));
itensTeste.splice(1,0,{...itensTeste[0],numero_questao:2});
export function paginaRevisao({tema='light',semRecorte=false,semFoto=false}={}) {
 const fonte=readFileSync(new URL('../../apps/web/resources/views/correcao/revisao.blade.php',import.meta.url),'utf8');
 let conteudo=fonte.split("@section('content')")[1].split('@endsection')[0];
 conteudo=conteudo.replace(/<x-pagina[\s\S]*?<\/x-pagina>/,'<div class="g-pagina"><h1 class="page-title">Revisar leituras</h1></div>')
   .replace('<x-aviso tipo="erro">','<div class="g-aviso erro" role="alert">').replace('</x-aviso>','</div>');
 let script=fonte.split('<script>')[1].split('</script>')[0];
 const itens=itensTeste.map(q=>({...q,endireitada_url:semRecorte||semFoto?null:q.endireitada_url,foto_url:semFoto?null:q.foto_url}));
 script=script.replace('@json($itens)',JSON.stringify(itens)).replace("{{ (int) ($prova['num_alternativas'] ?? 5) }}",'5')
   .replace("@json(route('correcao.show', $prova['id'], false))",'"/revisao"').replace('{{ csrf_token() }}','csrf-ficticio');
 if(script.includes('{{')||script.includes('@json'))throw new Error('Fixture exige revisão dos dados Blade.');
 return `<!doctype html><html lang="pt-BR" data-theme="${tema==='dark'?'dark':'light'}"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="g360-contexto" content="${'a'.repeat(48)}"><link rel="stylesheet" href="/css/app.css"><script src="/js/contexto-aba.js"></script><script src="/js/leitor-cartao.js"></script><body class="area-logada tela-revisao"><main class="container" style="padding-top:24px;padding-bottom:24px"><p>Ensaio local — dados fictícios</p>${conteudo}</main><footer aria-label="Versão do software" style="position:fixed;bottom:0;left:0;right:0;height:44px;background:var(--surface-2)">Ensaio PDF — dados fictícios</footer><script>${script}</script></body></html>`;
}
