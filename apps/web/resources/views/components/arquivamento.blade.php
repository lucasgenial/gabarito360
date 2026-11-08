@props(['registro','tipo','iconeSomente'=>false])
@php($arquivado=!$registro['ativo'])
@php($rotulo=($arquivado?'Desarquivar':'Arquivar').($tipo==='turmas'?' turma':' cadastro do aluno'))
<form method="POST" action="{{ route($tipo.'.arquivamento',$registro['id']) }}" data-arquivamento-titulo="{{ $rotulo }}" data-arquivamento-nome="{{ $registro['nome'] }}" data-arquivamento-verbo="{{ $arquivado?'Desarquivar':'Arquivar' }}" data-arquivamento-confirmar="{{ $rotulo }} {{ $registro['nome'] }}? {{ $arquivado?'O cadastro voltará a ficar disponível.':'O histórico será preservado e o cadastro sairá das listas de ativos.' }}">
@csrf @method('PUT') <x-revisao-acesso />
<input type="hidden" name="arquivado" value="{{ (int)!$arquivado }}"><input type="hidden" name="esperado_ativo" value="{{ (int)$registro['ativo'] }}"><input type="hidden" name="versao" value="{{ $registro['arquivamento']['versao']??0 }}"><input type="hidden" name="chave" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
<button type="submit" class="btn btn-secondary g360-arquivar-acao {{ $iconeSomente?'tu1-icone-acao':'btn-sm' }}" aria-label="{{ $rotulo }}" title="{{ $rotulo }}"><x-icone nome="arquivo" />@unless($iconeSomente) <span>{{ $arquivado?'Desarquivar':'Arquivar' }}@if($tipo==='turmas')<span class="acao-turma-contexto"> turma</span>@endif</span>@endunless</button>
</form>
@once
@push('styles')<link rel="stylesheet" href="{{ asset('css/arquivamento.css') }}?v={{ filemtime(public_path('css/arquivamento.css')) }}">@endpush
@push('scripts')
<dialog class="tu1-dialog g360-arquivar-dialog" data-arquivamento-modal aria-labelledby="arquivamento-titulo" aria-describedby="arquivamento-efeito">
<div class="g360-arquivar-cabecalho"><h2 id="arquivamento-titulo" data-arquivamento-titulo>Arquivar cadastro</h2><button class="btn btn-ghost" type="button" data-arquivamento-cancelar aria-label="Fechar confirmação" title="Fechar"><x-icone nome="fechar" /></button></div>
<p class="g360-arquivar-registro" data-arquivamento-registro></p><p id="arquivamento-efeito" data-arquivamento-efeito></p>
<div class="g360-arquivar-acoes"><button type="button" class="btn btn-secondary" data-arquivamento-cancelar><x-icone nome="fechar" /> Cancelar</button><button type="button" class="btn g360-arquivar-confirmar" data-arquivamento-confirmar-acao><x-icone nome="arquivo" /><span data-arquivamento-confirmar-rotulo>Arquivar</span></button></div>
</dialog>
<script src="{{ asset('js/arquivamento.js') }}?v={{ filemtime(public_path('js/arquivamento.js')) }}" defer></script>
@endpush
@endonce
