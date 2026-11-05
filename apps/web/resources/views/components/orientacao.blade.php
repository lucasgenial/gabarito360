@props(['assunto'])
<details {{ $attributes->class(['dc1-orientacao']) }}>
  <summary><span class="dc1-orientacao-abrir">Saiba mais <span aria-hidden="true">+</span></span><span class="dc1-orientacao-fechar">Saiba menos <span aria-hidden="true">−</span></span><span class="sr-only"> sobre {{ $assunto }}</span></summary>
  <div class="dc1-orientacao-conteudo">{{ $slot }}</div>
</details>
