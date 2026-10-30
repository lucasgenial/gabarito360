{{--
  Arquivo: apps/web/resources/views/dashboard/_provas-ciclo.blade.php
  Função: Lista de provas do painel com as ações do ciclo de avaliação
          (🖨 Cartões por turma · 📷 Corrigir · 📊 Resultados), visíveis conforme o perfil.
  Espera: $provas = [{id, titulo, disciplina?, status, turmas_lista[{id,nome}], total_alunos,
          cartoes_lidos?, professor?, data_aplicacao?}]
--}}
@php
  $statusCiclo = [
    'rascunho'    => ['badge-muted',   'rascunho'],
    'publicada'   => ['badge-warn',    'publicada'],
    'em_correcao' => ['badge-info',    'em correção'],
    'corrigida'   => ['badge-success', 'corrigida'],
  ];
@endphp
<style>
  .ciclo-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; }
  .ciclo-item { border:1px solid var(--border-soft); border-radius:var(--radius-md); padding:14px; background:var(--surface); }
  .ciclo-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
  .ciclo-top b { font-size:15px; }
  .ciclo-meta { font-size:12px; color:var(--muted); margin-top:2px; }
  .ciclo-bar { height:6px; border-radius:3px; background:var(--surface-2); margin-top:10px; overflow:hidden; }
  .ciclo-bar span { display:block; height:100%; background:var(--accent); }
  .ciclo-acoes { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
  .ciclo-acoes .btn { padding:8px 12px; font-size:13px; }
</style>
<ul class="ciclo-list">
  @forelse($provas as $p)
    @php
      [$badge, $rotulo] = $statusCiclo[$p['status']] ?? ['badge-muted', $p['status']];
      $aplicavel = in_array($p['status'], ['publicada', 'em_correcao', 'corrigida'], true);
      $lidos     = (int) ($p['cartoes_lidos'] ?? 0);
      $total     = (int) ($p['total_alunos'] ?? 0);
      $pct       = $total > 0 ? min(100, round($lidos / $total * 100)) : 0;
    @endphp
    <li class="ciclo-item">
      <div class="ciclo-top">
        <div style="min-width:0;">
          <b>{{ $p['disciplina'] ?? $p['titulo'] }}</b>
          <div class="ciclo-meta">
            {{ $p['titulo'] }}
            @if(!empty($p['turmas_lista'])) · {{ collect($p['turmas_lista'])->pluck('nome')->implode(', ') }}@endif
            @if(!empty($p['professor'])) · {{ $p['professor'] }}@endif
            @if(!empty($p['data_aplicacao'])) · {{ \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m') }}@endif
          </div>
        </div>
        <span style="display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end;">
          <span class="badge {{ $badge }}">{{ $rotulo }}</span>
          @if(!empty($p['correcao_fechada_em']))<span class="badge badge-warn" title="Correção fechada">🔒 fechada</span>@endif
        </span>
      </div>

      @if($aplicavel)
        <div class="ciclo-meta" style="margin-top:8px;">{{ $lidos }} de {{ $total }} cartões lidos</div>
        <div class="ciclo-bar"><span style="width:{{ $pct }}%"></span></div>
      @endif

      <div class="ciclo-acoes">
        @if($p['status'] === 'rascunho')
          @pode('prova.editar')
            <a class="btn btn-primary" href="{{ route('provas.gabarito.edit', $p['id']) }}">Continuar gabarito</a>
          @endpode
        @else
          @pode('cartao.enviar')
            <a class="btn btn-primary" href="{{ route('correcao.show', $p['id']) }}#camera">📷 Corrigir</a>
          @endpode
          @pode('cartao.imprimir')
            @foreach($p['turmas_lista'] ?? [] as $t)
              <a class="btn btn-secondary" target="_blank" href="{{ route('correcao.pdf', $p['id']) }}?turma_id={{ $t['id'] }}" title="Cartões-resposta com nome e QR">🖨 {{ $t['nome'] }}</a>
            @endforeach
          @endpode
          @if(in_array($p['status'], ['em_correcao', 'corrigida'], true))
            @pode('resultado.ver')
              <a class="btn btn-ghost" href="{{ route('relatorios.prova', $p['id']) }}">📊 Resultados</a>
            @endpode
          @endif
        @endif
        @pode('gabarito.ver')
          <a class="btn btn-ghost" href="{{ route('provas.show', $p['id']) }}">Detalhes</a>
        @endpode
      </div>
    </li>
  @empty
    <li style="text-align:center;color:var(--muted);padding:24px;">Nenhuma prova para você ainda.</li>
  @endforelse
</ul>
