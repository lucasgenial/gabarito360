<footer class="lp-rodape rodape-publico">
  <div class="rodape-publico-grade">
    <div class="rodape-publico-marca">
      <a class="brand" href="{{ route('home') }}" aria-label="Gabarito360 — página inicial"><span class="logo">G360</span> Gabarito360</a>
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
      <ul class="rodape-redes" aria-label="Perfis oficiais em preparação">
        @foreach(['Instagram', 'Facebook', 'YouTube', 'TikTok'] as $rede)
          <li><span>{{ $rede }}</span><span class="rodape-em-breve">Em breve</span></li>
        @endforeach
      </ul>
    </div>
  </div>
</footer>
