<dialog id="troca-contexto-modal" class="troca-contexto-modal" aria-labelledby="troca-contexto-titulo" aria-describedby="troca-contexto-orientacao">
  <div class="troca-contexto-cabecalho">
    <h2 id="troca-contexto-titulo">{{ \App\Support\Acesso::interno() ? 'Trocar acesso' : 'Trocar vínculo' }}</h2>
    <button type="button" class="btn btn-secondary" id="troca-contexto-fechar" autofocus>Fechar</button>
  </div>
  <p id="troca-contexto-orientacao">Escolha onde deseja trabalhar nesta aba.</p>
  <p id="troca-contexto-erro" class="field-error" role="alert" tabindex="-1" hidden></p>
  <div class="troca-contexto-opcoes">
    @foreach($opcoesTroca['opcoes'] as $opcao)
      @if($opcao['atual'])
        <div class="troca-contexto-opcao">
          <div><strong>{{ $opcao['nome'] }}</strong><p>{{ \App\Support\Acesso::rotulo($opcao['perfil']) }}</p></div>
          <span class="badge badge-info">Atual</span>
        </div>
      @else
        <form method="POST" action="{{ route($opcao['tipo']==='interno' ? 'vinculos.interno':'vinculos.store') }}" class="troca-contexto-opcao" data-sem-contexto>
          @csrf
          <input type="hidden" name="{{ $opcao['tipo']==='interno' ? 'acesso_interno_id':'vinculo_id' }}" value="{{ $opcao['id'] }}">
          <div><strong>{{ $opcao['nome'] }}</strong><p>{{ \App\Support\Acesso::rotulo($opcao['perfil']) }}@if($opcao['tipo']==='interno') · Equipe interna @endif</p></div>
          <button type="submit" class="btn btn-primary" aria-label="Entrar em {{ $opcao['nome'] }} como {{ \App\Support\Acesso::rotulo($opcao['perfil']) }}">Entrar</button>
        </form>
      @endif
    @endforeach
  </div>
</dialog>
<script>
(function () {
  var botao=document.getElementById('contexto-trocar'), modal=document.getElementById('troca-contexto-modal');
  if (!botao || !modal) return;
  botao.hidden=false;
  botao.addEventListener('click',function () {
    if (typeof modal.showModal !== 'function') { window.location.href=botao.dataset.alternativa; return; }
    modal.showModal();
  });
  document.getElementById('troca-contexto-fechar').addEventListener('click',function(){modal.close();});
  modal.addEventListener('close',function(){botao.focus();});
  var ocupado=false, fechar=document.getElementById('troca-contexto-fechar'), erro=document.getElementById('troca-contexto-erro');
  modal.addEventListener('cancel',function(e){if(ocupado)e.preventDefault();});
  modal.querySelectorAll('form').forEach(function(form){form.addEventListener('submit',async function(e){
    e.preventDefault(); if(ocupado)return;
    ocupado=true; erro.hidden=true; fechar.disabled=true; modal.setAttribute('aria-busy','true');
    var botoes=modal.querySelectorAll('button[type="submit"]'); botoes.forEach(function(b){b.disabled=true;});
    var controle=new AbortController(), limite=setTimeout(function(){controle.abort();},20000);
    var origem=e.submitter, textoOrigem=origem ? origem.textContent : '';
    if(origem)origem.textContent='Entrando…';
    try {
      var r=await fetch(form.action,{method:'POST',headers:{Accept:'application/json'},body:new FormData(form),signal:controle.signal});
      var dados=await r.json();
      if(!r.ok || !dados.url)throw new Error(dados.message || 'Não foi possível trocar o acesso. Tente novamente.');
      var destino=new URL(dados.url,location.href);
      if(destino.origin!==location.origin)throw new Error('Não foi possível confirmar o destino. Atualize a página.');
      location.assign(destino.href);
    } catch(e) {
      erro.textContent=(e.name==='AbortError' || e instanceof TypeError || e instanceof SyntaxError)
        ? 'Não foi possível confirmar a troca. Atualize a página antes de tentar novamente.'
        : (e.message || 'Não foi possível confirmar a troca. Atualize a página antes de tentar novamente.');
      erro.hidden=false; erro.focus();
    } finally {
      clearTimeout(limite); if(origem)origem.textContent=textoOrigem;
      ocupado=false; fechar.disabled=false; botoes.forEach(function(b){b.disabled=false;}); modal.removeAttribute('aria-busy');
    }
  });});
})();
</script>
