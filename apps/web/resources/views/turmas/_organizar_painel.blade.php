<div class="painel-organizacao" data-painel-organizacao hidden>
 <button type="button" class="btn btn-secondary" data-grade-organizar disabled><x-icone nome="editar" /> Organizar painel</button>
 <div class="painel-organizacao-acoes" data-grade-acoes hidden><button type="button" class="btn btn-primary" data-grade-salvar><x-icone nome="salvar" /> Salvar disposição</button><button type="button" class="btn btn-secondary" data-grade-cancelar><x-icone nome="fechar" /> Cancelar</button><button type="button" class="btn btn-ghost" data-grade-restaurar>Restaurar padrão</button></div>
 <button type="button" class="btn btn-secondary" data-grade-consultar hidden>Consultar estado salvo</button>
 <p role="status" aria-live="polite" data-grade-status></p>
</div>
@php
$alvosPainel=['painel'=>$paginaPainel];
if($paginaPainel==='turma')$alvosPainel['turmaId']=$turmaPainel??$turma['id'];
@endphp
<script type="application/json" id="painel-grade-config">{!! json_encode(['painel'=>$paginaPainel,'consultar'=>\App\Support\NavegacaoWeb::destino('paineis.preferencias.consultar',$alvosPainel),'salvar'=>\App\Support\NavegacaoWeb::destino('paineis.preferencias.salvar',$alvosPainel)],JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) !!}</script>
@once
@push('styles')<link rel="stylesheet" href="{{ asset('css/paineis-personalizaveis.css') }}?v={{ @filemtime(public_path('css/paineis-personalizaveis.css')) }}"><link rel="stylesheet" href="{{ asset('css/painel-grade.css') }}?v={{ @filemtime(public_path('css/painel-grade.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/painel-graficos-opcoes.js') }}?v={{ @filemtime(public_path('js/painel-graficos-opcoes.js')) }}" defer></script><script src="{{ asset('js/painel-grade.js') }}?v={{ @filemtime(public_path('js/painel-grade.js')) }}" defer></script>@endpush
@endonce
