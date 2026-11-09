<dialog id="troca-contexto-modal" class="troca-contexto-modal" aria-labelledby="troca-contexto-titulo" aria-describedby="troca-contexto-orientacao">
  <div class="troca-contexto-cabecalho">
    <h2 id="troca-contexto-titulo">{{ \App\Support\Acesso::interno() ? 'Trocar acesso' : 'Trocar vínculo' }}</h2>
    <button type="button" class="btn btn-secondary" id="troca-contexto-fechar" autofocus>Fechar</button>
  </div>
  <p id="troca-contexto-orientacao">A mudança valerá para todas as abas abertas desta sessão.</p>
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
          @csrf <x-revisao-acesso />
          <input type="hidden" name="{{ $opcao['tipo']==='interno' ? 'acesso_interno_id':'vinculo_id' }}" value="{{ $opcao['id'] }}">
          <div><strong>{{ $opcao['nome'] }}</strong><p>{{ \App\Support\Acesso::rotulo($opcao['perfil']) }}@if($opcao['tipo']==='interno') · Equipe interna @endif</p></div>
          <button type="submit" class="btn btn-primary" aria-label="Entrar em {{ $opcao['nome'] }} como {{ \App\Support\Acesso::rotulo($opcao['perfil']) }}">Entrar</button>
        </form>
      @endif
    @endforeach
  </div>
</dialog>
<script src="@ativo('js/troca-contexto.js')"></script>
