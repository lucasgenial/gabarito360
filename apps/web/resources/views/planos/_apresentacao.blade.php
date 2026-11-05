@php($nivel = ($heading ?? 'h3') === 'h2' ? 'h2' : 'h3')
<p class="publico-modalidades-intro">{{ config('apresentacao.modalidades_intro') }}</p>
<div class="plans-grid publico-planos">
  @foreach(config('apresentacao.modalidades') as $modalidade)
    <article class="plan-card">
      <{{ $nivel }} class="plan-name">{{ $modalidade['nome'] }}</{{ $nivel }}>
      <p class="plan-desc">{{ $modalidade['descricao'] }}</p>
      <ul class="plan-features">@foreach($modalidade['itens'] as $item)<li>{{ $item }}</li>@endforeach</ul>
      @if(isset($modalidade['futuro']))
        <div class="publico-recurso" data-recurso-futuro>
          <span class="publico-em-breve">Em breve</span>
          <strong>{{ $modalidade['futuro']['nome'] }}</strong>
          <p class="plan-desc">{{ $modalidade['futuro']['descricao'] }}</p>
        </div>
      @endif
    </article>
  @endforeach
</div>
