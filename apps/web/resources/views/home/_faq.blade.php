<div class="publico-faq">
  @foreach(config('apresentacao.faq') as $duvida)
    <details><summary>{{ $duvida['pergunta'] }}</summary><p>{{ $duvida['resposta'] }}</p></details>
  @endforeach
</div>
