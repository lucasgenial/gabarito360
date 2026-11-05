<p class="lp-sub">O Gabarito360 está em apresentação. Os planos e as condições de atendimento serão definidos posteriormente.</p>
<div class="plans-grid publico-planos">
  @foreach(config('apresentacao.modalidades') as $modalidade)
    <article class="plan-card">
      <h3 class="plan-name">{{ $modalidade['nome'] }}</h3>
      <p class="plan-desc">{{ $modalidade['descricao'] }}</p>
      <ul class="plan-features">
        @foreach($modalidade['itens'] as $item)<li>{{ $item }}</li>@endforeach
      </ul>
    </article>
  @endforeach
</div>
<section class="publico-faq" aria-label="Recursos do Gabarito360">
  <h3>Disponível no sistema</h3>
  <details><summary>Saiba mais <span aria-hidden="true">+</span></summary><ul class="plan-features">
    @foreach(config('apresentacao.disponiveis') as $item)<li>{{ $item }}</li>@endforeach
  </ul></details>
  <h3>Previsto no planejamento</h3>
  <details><summary>Saiba mais <span aria-hidden="true">+</span></summary>
    <p>Estes recursos ainda serão desenvolvidos. Não estão disponíveis nesta apresentação.</p>
    <ul class="plan-features">@foreach(config('apresentacao.previstos') as $item)<li>{{ $item }}</li>@endforeach</ul>
  </details>
</section>
<section class="publico-contato">
  <div><h3>Vamos conversar sobre sua rotina?</h3><p>Conte como você ou sua instituição realiza as avaliações.</p></div>
  <a class="btn btn-primary" href="{{ route('leads.index') }}">Fale conosco</a>
</section>
