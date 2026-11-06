<div class="card"><div class="card-pad">
{{-- Seção: Provas da turma --}}
<h2 class="section-title">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
    <path d="M14 2v6h6"/>
  </svg>
  Provas da Turma
  <span class="badge" style="margin-left:6px;font-size:13px;">{{ count($turma['provas'] ?? []) }}</span>
</h2>

</div>
  @if(count($turma['provas'] ?? []) > 0)
  <x-tabela :interna="true" :integrada="true" titulo="Provas da turma" modo="lista"><table class="tabela-padrao table "><caption class="sr-only">Provas da turma</caption>
    <thead>
      <tr>
        <th scope="col">Prova</th>
        <th scope="col">Disciplina</th>
        <th scope="col">Aplicação</th>
        <th scope="col">Média</th>
        <th scope="col">Status</th>
        <th scope="col">Ações</th>
      </tr>
    </thead>
    <tbody>
      @php
        $statusCfg = [
          'rascunho'    => ['cls'=>'badge-info',   'label'=>'Rascunho'],
          'publicada'   => ['cls'=>'badge-success', 'label'=>'Publicada'],
          'em_correcao' => ['cls'=>'badge-warn', 'label'=>'Em correção', 'style'=>''],
          'corrigida'   => ['cls'=>'badge-success', 'label'=>'Corrigida'],
        ];
      @endphp
      @foreach($turma['provas'] as $p)
        @php $sc = $statusCfg[$p['status']] ?? ['cls'=>'badge-info','label'=>$p['status']]; @endphp
        <tr>
          <td class="tabela-principal" data-rotulo="Prova"><div class="tabela-valor"><strong>{{ $p['titulo'] }}</strong></div></td>
          <td data-rotulo="Disciplina"><div class="tabela-valor">{{ $p['disciplina'] }}</div></td>
          <td data-rotulo="Aplicação"><div class="tabela-valor">{{ $p['data_aplicacao'] ? \Carbon\Carbon::parse($p['data_aplicacao'])->format('d/m/Y') : '—' }}</div></td>
          <td style="font-family:var(--font-mono);" data-rotulo="Média"><div class="tabela-valor">{{ $p['media'] !== null ? number_format($p['media'], 1, ',', '') : '—' }}</div></td>
          <td data-rotulo="Status"><div class="tabela-valor"><span class="badge {{ $sc['cls'] }}" style="{{ $sc['style'] ?? '' }}">{{ $sc['label'] }}</span></div></td>
          <td class="tabela-acoes" data-rotulo="Ações"><div class="tabela-valor">
            @if($p['status'] === 'em_correcao')
              <a href="{{ route('correcao.show', $p['id']) }}" class="btn btn-sm btn-secondary"><x-icone nome="grafico" /> Acompanhar</a>
            @elseif($p['status'] === 'corrigida')
              <a href="{{ route('relatorios.turmaProva', [$turma['id'], $p['id']]) }}" class="btn btn-sm btn-secondary"><x-icone nome="pdf" /> Relatório</a>
            @else
              <a href="{{ route('provas.show', $p['id']) }}" class="btn btn-sm btn-secondary"><x-icone nome="ver" /> Ver</a>
            @endif
          </div></td>
        </tr>
      @endforeach
    </tbody>
  </table></x-tabela>
  @else
  <div style="text-align:center;padding:48px 20px;color:var(--muted);">
    <div class="icone-grande"><x-icone nome="prova" /></div>
    <div style="font-size:15px;font-weight:600;color:var(--fg-2);margin-bottom:6px;">Nenhuma prova aplicada</div>
    <p>As provas vinculadas a esta turma aparecerão aqui.</p>
  </div>
  @endif
</div>

