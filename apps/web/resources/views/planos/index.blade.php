@extends('layouts.public')
@section('title', 'Planos e Preços — Gabarito360')

@push('styles')
<style>
.planos-hero {
  text-align: center;
  padding: 3rem 0 2rem;
}
.planos-hero h1 {
  font-size: 2rem;
  font-weight: 800;
  color: var(--color-primary-default);
  margin-bottom: .5rem;
}
.planos-hero p {
  color: var(--color-secondary-07);
  font-size: 1.05rem;
  max-width: 540px;
  margin: 0 auto;
}

.planos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin: 2rem 0;
}
.plano-card {
  background: var(--color-secondary-01);
  border: 1px solid var(--color-secondary-03);
  border-radius: 8px;
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
  position: relative;
}
.plano-card.destaque {
  border-color: var(--color-primary-default);
  box-shadow: 0 0 0 2px var(--color-primary-default);
}
.plano-card.destaque::before {
  content: 'Mais popular';
  position: absolute;
  top: -1px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--color-primary-default);
  color: #fff;
  font-size: .75rem;
  font-weight: 700;
  padding: .2rem .8rem;
  border-radius: 0 0 6px 6px;
  white-space: nowrap;
}
.plano-nome {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-secondary-09);
  margin-bottom: .5rem;
}
.plano-preco {
  font-size: 2rem;
  font-weight: 800;
  color: var(--color-primary-default);
  line-height: 1.1;
}
.plano-preco small {
  font-size: .875rem;
  font-weight: 400;
  color: var(--color-secondary-06);
}
.plano-preco-desc {
  font-size: .8rem;
  color: var(--color-secondary-06);
  margin-bottom: 1rem;
}
.plano-economia {
  display: inline-block;
  background: var(--color-success-01);
  color: var(--color-success-03);
  font-size: .75rem;
  font-weight: 700;
  padding: .15rem .6rem;
  border-radius: 99px;
  margin-bottom: .75rem;
}
.plano-limites {
  list-style: none;
  padding: 0;
  margin: 1rem 0;
  flex: 1;
}
.plano-limites li {
  font-size: .875rem;
  color: var(--color-secondary-07);
  padding: .3rem 0;
  border-bottom: 1px solid var(--color-secondary-02);
}
.plano-limites li::before {
  content: '✓ ';
  color: var(--color-success-03);
  font-weight: 700;
}
.plano-cta {
  margin-top: auto;
}
.plano-cta .btn {
  width: 100%;
  text-align: center;
  justify-content: center;
}

.secao-comercial {
  background: var(--color-primary-02);
  border-radius: 8px;
  padding: 2rem;
  margin: 2.5rem 0;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 1.5rem;
  align-items: center;
}
.secao-comercial h2 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-primary-default);
  margin-bottom: .5rem;
}
.secao-comercial p {
  color: var(--color-secondary-07);
  font-size: .9rem;
}

.secao-faq {
  margin: 3rem 0;
}
.secao-faq h2 {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}
.faq-item {
  border-bottom: 1px solid var(--color-secondary-03);
  padding: 1rem 0;
}
.faq-pergunta {
  font-weight: 600;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: .95rem;
}
.faq-resposta {
  font-size: .875rem;
  color: var(--color-secondary-07);
  margin-top: .5rem;
  display: none;
}
.faq-item.aberto .faq-resposta { display: block; }
.faq-item.aberto .faq-icon { transform: rotate(180deg); }
.faq-icon { transition: transform .2s; }
</style>
@endpush

@section('content')
<div class="planos-hero">
  <h1>Planos e Preços</h1>
  <p>Escolha o plano ideal para o seu contexto — professor individual ou instituição de ensino.</p>
</div>

{{-- Planos autoatendimento --}}
<div class="planos-grid">
  @foreach($individuais as $p)
  <div class="plano-card {{ $loop->index === 1 ? 'destaque' : '' }}">
    <div class="plano-nome">{{ $p['nome'] }}</div>

    <div class="plano-preco">
      R$ {{ number_format($p['preco_fixo'], 2, ',', '.') }}
      <small>/{{ $p['periodicidade'] === 'anual' ? 'ano' : 'mês' }}</small>
    </div>

    @if($p['periodicidade'] === 'anual')
    <div class="plano-preco-desc">equivale a R$ {{ number_format($p['preco_fixo'] / 12, 2, ',', '.') }}/mês</div>
    <span class="plano-economia">Economia de 16%</span>
    @else
    <div class="plano-preco-desc">cobrado mensalmente</div>
    @endif

    <ul class="plano-limites">
      <li>1 escola</li>
      <li>Até 20 turmas</li>
      <li>Até 50 provas por mês</li>
      <li>OMR (leitura de cartões)</li>
      <li>Relatórios completos</li>
    </ul>

    <div class="plano-cta">
      <a href="{{ route('leads.index') }}" class="btn btn-primary">Falar com a gente</a>
    </div>
  </div>
  @endforeach

  @foreach($institucional as $p)
  <div class="plano-card">
    <div class="plano-nome">{{ $p['nome'] }}</div>

    <div class="plano-preco">
      R$ {{ number_format($p['preco_por_licenca'], 2, ',', '.') }}
      <small>/licença/mês</small>
    </div>
    <div class="plano-preco-desc">de {{ $p['licencas_min'] }} até {{ $p['licencas_max'] }} licenças</div>

    <ul class="plano-limites">
      <li>Até 5 escolas</li>
      <li>Até 100 turmas</li>
      <li>Até 200 provas por mês</li>
      <li>OMR (leitura de cartões)</li>
      <li>Relatórios consolidados</li>
      <li>Gestão de equipe</li>
    </ul>

    <div class="plano-cta">
      <a href="{{ route('leads.index') }}" class="btn btn-primary">Falar com a gente</a>
    </div>
  </div>
  @endforeach
</div>

{{-- Planos comerciais --}}
<div class="secao-comercial">
  <div>
    <h2>Mais de 10 licenças? Secretaria ou Rede Municipal?</h2>
    <p>Para redes maiores e secretarias de educação, trabalhamos com proposta personalizada. Sem preço de tabela — nosso time entra em contato para entender sua demanda.</p>
  </div>
  <div style="flex-shrink:0">
    <a href="{{ route('leads.index') }}" class="btn btn-outline" style="white-space:nowrap">Fale Conosco</a>
  </div>
</div>

{{-- FAQ --}}
<div class="secao-faq">
  <h2>Dúvidas frequentes</h2>

  @php
  $faqs = [
    ['O que é uma licença?', 'Uma licença corresponde a 1 professor ou usuário ativo dentro da sua rede institucional. Alunos e aplicadores não contam como licença.'],
    ['Meus dados ficam seguros se eu cancelar?', 'Sim. O cancelamento suspende o acesso, mas seus dados são preservados por 90 dias antes de qualquer exclusão.'],
    ['Posso migrar do plano individual para o institucional?', 'Sim. Se sua escola ou rede decidir usar o plano institucional, transferimos todo o histórico de provas e alunos sem perda de dados.'],
  ];
  @endphp

  @foreach($faqs as $faq)
  <div class="faq-item">
    <div class="faq-pergunta" onclick="this.closest('.faq-item').classList.toggle('aberto')">
      {{ $faq[0] }}
      <span class="faq-icon">▼</span>
    </div>
    <div class="faq-resposta">{{ $faq[1] }}</div>
  </div>
  @endforeach
</div>
@endsection
