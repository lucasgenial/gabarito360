@if($errors->any())
<div class="catalogo-erros" role="alert" tabindex="-1" data-resumo-erros>
  <h2>Confira antes de continuar</h2>
  <ul>@foreach($errors->all() as $mensagem)<li>{{ $mensagem }}</li>@endforeach</ul>
</div>
@elseif(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
