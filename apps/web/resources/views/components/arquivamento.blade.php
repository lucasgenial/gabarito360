@props(['registro','tipo','iconeSomente'=>false])
@php($arquivado=!$registro['ativo'])
@php($rotulo=($arquivado?'Desarquivar':'Arquivar').($tipo==='turmas'?' turma':' cadastro do aluno'))
<form method="POST" action="{{ route($tipo.'.arquivamento',$registro['id']) }}" data-arquivamento-confirmar="{{ $rotulo }} {{ $registro['nome'] }}? {{ $arquivado?'O cadastro voltará a ficar disponível.':'O histórico será preservado e o cadastro sairá das listas de ativos.' }}">
@csrf @method('PUT') <x-revisao-acesso />
<input type="hidden" name="arquivado" value="{{ (int)!$arquivado }}"><input type="hidden" name="esperado_ativo" value="{{ (int)$registro['ativo'] }}"><input type="hidden" name="versao" value="{{ $registro['arquivamento']['versao']??0 }}"><input type="hidden" name="chave" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
<button type="submit" class="btn btn-secondary {{ $iconeSomente?'tu1-icone-acao':'btn-sm' }}" aria-label="{{ $rotulo }}" title="{{ $rotulo }}"><x-icone nome="arquivo" />@unless($iconeSomente) {{ $arquivado?'Desarquivar':'Arquivar' }}@endunless</button>
</form>
@once @push('scripts')<script src="{{ asset('js/arquivamento.js') }}?v={{ filemtime(public_path('js/arquivamento.js')) }}" defer></script>@endpush @endonce
