<footer class="lp-rodape rodape-publico">
  <div class="rodape-publico-grade">
    <div class="rodape-publico-marca">
      <a class="brand" href="{{ route('home') }}" aria-label="Gabarito360 — página inicial"><x-marca /></a>
      <p>Correção de provas e relatórios educacionais.</p>
      <p>© {{ date('Y') }} Gabarito360</p>
    </div>
    <nav aria-label="Conheça o Gabarito360">
      <h2>Conheça</h2>
      <a href="{{ route('home') }}#como-funciona">Como funciona</a>
      <a href="{{ route('home') }}#recursos">Recursos</a>
      <a href="{{ route('planos.index') }}">Planos</a>
    </nav>
    <nav aria-label="Contato e acesso">
      <h2>Contato e acesso</h2>
      <a href="{{ route('leads.index') }}">Fale conosco</a>
      <a href="{{ route('login') }}">Entrar</a>
      <a href="{{ route('esqueci-senha') }}">Recuperar senha</a>
    </nav>
    <div>
      <h2>Redes sociais</h2>
      <ul class="rodape-redes" aria-label="Redes sociais oficiais">
        <li><a href="https://www.instagram.com/gabarito360/" target="_blank" rel="noopener noreferrer" aria-label="Instagram @gabarito360 (abre em nova aba)">Instagram · @gabarito360</a></li>
        <li><a href="https://www.facebook.com/gabarito360/" target="_blank" rel="noopener noreferrer" aria-label="Facebook @gabarito360 (abre em nova aba)">Facebook · @gabarito360</a></li>
        <li><a href="https://www.tiktok.com/@gabarito360" target="_blank" rel="noopener noreferrer" aria-label="TikTok @gabarito360 (abre em nova aba)">TikTok · @gabarito360</a></li>
        <li><a href="https://www.youtube.com/@gabarito360" target="_blank" rel="noopener noreferrer" aria-label="YouTube @gabarito360 (abre em nova aba)">YouTube · @gabarito360</a></li>
        @foreach(['YouTube'] as $rede)
          <li><span>{{ $rede }}</span><span class="rodape-em-breve">Em breve</span></li>
        @endforeach
      </ul>
    </div>
  </div>
</footer>
