<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<meta name="description" content="Gabarito360 lê cartões-resposta pela câmera do celular, calcula as notas e monta os relatórios por aluno, turma e escola."/>
<title>Gabarito360: correção de provas pelo celular</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}"/>
</head>
<body class="area-publica tela-inicio">

<nav class="lp-nav" aria-label="Navegação principal">
  <div class="lp-nav-inner">
    <a href="{{ route('home') }}" class="brand"><span class="logo">G360</span> Gabarito360</a>
    <div class="lp-links">
      <a href="#como-funciona">Como funciona</a>
      <a href="#recursos">Recursos</a>
      <a href="#planos">Planos</a>
    </div>
    <div class="lp-acoes">
      <a href="{{ route('login') }}" class="btn btn-ghost">Entrar</a>
      <a href="{{ route('leads.index') }}" class="btn btn-primary">Falar com a gente</a>
    </div>
  </div>
</nav>

<section class="lp-abertura" aria-labelledby="titulo">
  <div class="lp-inner lp-abertura-grade">
    <div class="lp-texto">
      <h1 id="titulo">Da prova impressa à nota da turma, pelo celular.</h1>
      <p>Imprima o cartão-resposta, aplique a prova e fotografe cada cartão. O Gabarito360 lê as marcações, calcula as notas e monta os relatórios.</p>
      <div class="lp-acoes">
        <a href="{{ route('leads.index') }}" class="btn btn-primary">Falar com a gente</a>
        <a href="#como-funciona" class="btn btn-secondary">Ver como funciona</a>
      </div>
    </div>
    <figure class="lp-produto">
      <div class="lp-cartao" aria-hidden="true">
        @foreach([['01', 'B'], ['02', 'D'], ['03', 'A'], ['04', 'C'], ['05', 'E']] as [$q, $m])
          <div class="lp-linha"><b>{{ $q }}</b>@foreach(['A', 'B', 'C', 'D', 'E'] as $l)<span class="{{ $l === $m ? 'marcada' : '' }}">{{ $l }}</span>@endforeach</div>
        @endforeach
      </div>
      <div class="lp-resultado">
        <div class="lp-resultado-topo"><b>3º A</b><span>42 de 54 cartões</span></div>
        <div class="progress-track"><div style="width:78%"></div></div>
        <table>
          <tr><td>Aluno de exemplo 1</td><td>8,5</td></tr>
          <tr><td>Aluno de exemplo 2</td><td>6,0</td></tr>
          <tr><td>Aluno de exemplo 3</td><td>7,5</td></tr>
        </table>
        <p class="lp-revisar">5 leituras para revisar</p>
      </div>
      <figcaption>Exemplo com dados fictícios.</figcaption>
    </figure>
  </div>
</section>

<section class="lp-secao" id="como-funciona" aria-labelledby="t-como">
  <div class="lp-inner">
    <h2 id="t-como">Como funciona</h2>
    <p class="lp-sub">Você usa a impressora da escola e o celular que já tem. Nenhum scanner é necessário.</p>
    <ol class="lp-passos">
      <li><h3>Prepare a prova</h3><p>Cadastre o gabarito e gere os cartões-resposta em PDF, um para cada aluno.</p></li>
      <li><h3>Aplique no papel</h3><p>Os alunos marcam o cartão impresso, como em qualquer prova.</p></li>
      <li><h3>Fotografe os cartões</h3><p>Aponte a câmera para cada cartão. O sistema identifica o aluno e lê as respostas.</p></li>
      <li><h3>Confira as dúvidas</h3><p>Quando uma marcação fica incerta, você vê a linha do cartão e decide a resposta.</p></li>
    </ol>
  </div>
</section>

{{-- Pesquisas: só dados publicados, com fonte. Estudos de terceiros, não medições do Gabarito360 --}}
<section class="lp-secao lp-alternada" aria-labelledby="t-pesquisas">
  <div class="lp-inner">
    <h2 id="t-pesquisas">O que as pesquisas dizem sobre corrigir e devolver resultados</h2>
    <div class="numbers-grid">
      <div class="number-card">
        <div class="number-val">6,1<small style="font-size:1.5rem"> h</small></div>
        <div class="number-label">por semana é o tempo que professores brasileiros em tempo integral relatam gastar corrigindo trabalhos de alunos; a média da OCDE é 4,6 h.</div>
        <a class="number-fonte" href="https://www.oecd.org/en/publications/results-from-talis-2024-country-notes_e127f9e2-en/brazil_1e93d3b5-en.html" target="_blank" rel="noopener">OCDE, TALIS 2024</a>
      </div>
      <div class="number-card">
        <div class="number-val">15<small style="font-size:1.5rem">/min</small></div>
        <div class="number-label">provas corrigidas com a câmera do celular em um estudo com 135 alunos, usando outro aplicativo. Os autores relatam menos erros e menos tempo de correção.</div>
        <a class="number-fonte" href="https://investigacionuch.ceu.es/documentos/6761cd249431f44fab674f3d" target="_blank" rel="noopener">Castillo García et al., In-RED 2019</a>
      </div>
      <div class="number-card">
        <div class="number-val">435</div>
        <div class="number-label">estudos com mais de 61 mil participantes mostram efeito médio do feedback na aprendizagem (d = 0,48), maior quanto mais informação ele traz.</div>
        <a class="number-fonte" href="https://pmc.ncbi.nlm.nih.gov/articles/PMC6987456" target="_blank" rel="noopener">Wisniewski, Zierer e Hattie, 2020</a>
      </div>
    </div>
  </div>
</section>

<section class="lp-secao" id="recursos" aria-labelledby="t-recursos">
  <div class="lp-inner">
    <h2 id="t-recursos">Recursos</h2>
    <div class="lp-recursos">
      <div><h3>Leitura pela câmera</h3><p>O celular lê o cartão e o QR identifica a prova e o aluno.</p></li></div>
      <div><h3>Relatórios</h3><p>Resultados por aluno, turma, escola, núcleo e rede.</p></div>
      <div><h3>Impressão e envio</h3><p>Resultados em A4 e PDF, prontos para imprimir ou enviar pelo WhatsApp.</p></div>
      <div><h3>Acesso por perfil</h3><p>Professor, coordenação, direção e núcleo veem o que cabe a cada função.</p></div>
    </div>
  </div>
</section>

<section class="lp-secao lp-alternada" id="planos" aria-labelledby="t-planos">
  <div class="lp-inner">
    <h2 id="t-planos">Planos</h2>
    <p class="lp-sub">Fale com a gente para escolher o plano e começar.</p>
    <div class="plans-grid">
      @foreach($planos as $plano)
        <div class="plan-card">
          <div class="plan-name">{{ $plano['publico'] === 'individual' ? 'Individual' : 'Institucional' }}</div>
          <div class="plan-price">
            @if($plano['preco_fixo'] !== null)
              R$ {{ number_format($plano['preco_fixo'], 2, ',', '.') }}
              <small>/{{ $plano['periodicidade'] === 'anual' ? 'ano' : 'mês' }}</small>
            @else
              R$ {{ number_format($plano['preco_por_licenca'], 2, ',', '.') }}
              <small>/licença/mês</small>
            @endif
          </div>
          <div class="plan-desc">
            @if($plano['periodicidade'] === 'anual')
              Equivale a R$ {{ number_format($plano['preco_fixo'] / 12, 2, ',', '.') }}/mês
            @elseif($plano['preco_por_licenca'] !== null)
              De {{ $plano['licencas_min'] }} a {{ $plano['licencas_max'] }} licenças (professores/usuários)
            @else
              Cobrado por mês
            @endif
          </div>
          <ul class="plan-features">
            @if($plano['publico'] === 'individual')
              <li>1 escola · até 20 turmas</li>
              <li>Leitura pela câmera</li>
              <li>Relatórios completos</li>
              <li>Suporte por e-mail</li>
            @else
              <li>Até 5 escolas · 100 turmas</li>
              <li>Leitura pela câmera</li>
              <li>Relatórios e dashboards</li>
              <li>Gestão de equipe</li>
            @endif
          </ul>
          <div class="plan-cta">
            <a href="{{ route('leads.index') }}">Falar com a gente</a>
          </div>
        </div>
      @endforeach

      <div class="plan-card">
        <div class="plan-name">Rede ou secretaria</div>
        <div class="plan-price">Sob consulta</div>
        <div class="plan-desc">Para redes acima de 10 licenças e secretarias de educação</div>
        <ul class="plan-features">
          <li>Várias escolas e núcleos</li>
          <li>Leitura pela câmera</li>
          <li>Relatórios consolidados</li>
        </ul>
        <div class="plan-cta"><a href="{{ route('leads.index') }}">Falar com a gente</a></div>
      </div>
    </div>
  </div>
</section>

<footer class="lp-rodape">
  <div class="lp-inner lp-rodape-grade">
    <strong>Gabarito360</strong>
    <div class="lp-links">
      <a href="{{ route('planos.index') }}">Planos</a>
      <a href="{{ route('leads.index') }}">Fale conosco</a>
      <a href="{{ route('login') }}">Entrar</a>
    </div>
    <span>© {{ date('Y') }} Gabarito360</span>
  </div>
</footer>

</body>
</html>
