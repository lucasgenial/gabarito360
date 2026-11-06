@extends('layouts.app')
@section('title','Transferir aluno entre turmas')
@section('breadcrumb')<a href="{{ route('turmas.show',['id'=>$aluno['turma_id'],'secao'=>'alunos']) }}">{{ $aluno['turma_nome'] ?? 'Turma' }} · Alunos</a><span class="sep">/</span><span>Transferir aluno</span>@endsection
@section('content')
<x-pagina titulo="Transferir aluno entre turmas"><x-slot:sub>{{ $aluno['nome'] }} · Origem: {{ $aluno['turma_nome'] }}</x-slot:sub></x-pagina>
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-aviso>@endif
<div class="card card-pad formulario-escolar">
<p>O histórico do aluno será preservado.</p>
<details class="tu1-ajuda"><summary>Saiba mais sobre a transferência</summary><p>A mudança vale a partir da confirmação. As provas, notas e imagens anteriores permanecem ligadas à turma em que foram aplicadas. A operação registra o responsável, o momento e o motivo da mudança.</p></details>
@if(count($turmas))
<form data-formulario-validado method="POST" action="{{ route('alunos.transferir',$aluno['id']) }}" data-turma-confirmar="Confirmar a transferência deste aluno para a turma selecionada? O histórico será preservado." data-turma-transferencia data-aluno="{{ $aluno['nome'] }}" data-origem="{{ $aluno['turma_nome'] }}">
@csrf <x-revisao-acesso /><input type="hidden" name="turma_origem_id" value="{{ old('turma_origem_id',$aluno['turma_id']) }}"><input type="hidden" name="chave" value="{{ old('chave',(string)\Illuminate\Support\Str::uuid()) }}">
<div class="form-row-2"><div class="field"><label for="destino">Turma de destino *</label><select id="destino" class="select" name="turma_id" required @error('turma_id') aria-invalid="true" aria-describedby="erro-turma_id" @enderror ><option value="">Selecione outra turma da mesma escola</option>@foreach($turmas as $t)<option value="{{ $t['id'] }}" @selected(old('turma_id')==$t['id'])>{{ $t['nome'] }} · {{ $t['serie'] }} · {{ $t['ano_letivo'] }}</option>@endforeach</select><x-erro-campo nome="turma_id" /></div>
<div class="field"><label for="motivo">Motivo da transferência *</label><textarea class="input" id="motivo" name="motivo" minlength="5" maxlength="500" required @error('motivo') aria-invalid="true" aria-describedby="erro-motivo" @enderror >{{ old('motivo') }}</textarea><x-erro-campo nome="motivo" /></div></div>
<div class="form-actions"><a class="btn btn-secondary" href="{{ route('turmas.show',['id'=>$aluno['turma_id'],'secao'=>'alunos']) }}">Cancelar</a><button class="btn btn-primary" type="submit" @disabled(!$aluno['ativo'])>Confirmar transferência</button></div>
</form>
@else<x-aviso tipo="info">Não há outra turma ativa e autorizada nesta escola.</x-aviso><a class="btn btn-secondary" href="{{ route('turmas.show',['id'=>$aluno['turma_id'],'secao'=>'alunos']) }}">Voltar aos alunos</a>@endif
</div>
@push('scripts')<script src="{{ asset('js/turmas.js') }}" defer></script>@endpush
@endsection

@push('scripts')<script src="{{ asset('js/formulario-erros.js') }}" defer></script>@endpush

@push('styles')<link rel="stylesheet" href="{{ asset('css/formulario-escolar.css') }}?v={{ filemtime(public_path('css/formulario-escolar.css')) }}">@endpush
