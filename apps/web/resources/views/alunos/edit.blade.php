@extends('layouts.app')
@section('title','Editar aluno')
@section('breadcrumb')<a href="{{ route('turmas.show',['id'=>$aluno['turma_id'],'secao'=>'alunos']) }}">{{ $aluno['turma_nome'] ?? 'Turma' }} · Alunos</a><span class="sep">/</span><span>Editar aluno</span>@endsection
@section('content')
<x-pagina titulo="Editar aluno"><x-slot:sub>{{ $aluno['nome'] }} · {{ $aluno['turma_nome'] ?? 'Turma' }}</x-slot:sub></x-pagina>
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-aviso>@endif
<form data-formulario-validado class="card card-pad formulario-escolar" method="POST" action="{{ route('alunos.update',$aluno['id']) }}">
@csrf <x-revisao-acesso /> @method('PUT')<input type="hidden" name="voltar_turma" value="1">
<div class="form-row-2">
<div class="field"><label for="nome">Nome *</label><input class="input" id="nome" name="nome" required maxlength="200" value="{{ old('nome',$aluno['nome']) }}" @error('nome') aria-invalid="true" aria-describedby="erro-nome" @enderror ><x-erro-campo nome="nome" /></div>
<div class="field"><label for="matricula">Matrícula *</label><input class="input" id="matricula" name="matricula" required maxlength="20" value="{{ old('matricula',$aluno['matricula']) }}" @error('matricula') aria-invalid="true" aria-describedby="erro-matricula" @enderror ><x-erro-campo nome="matricula" /></div>
@unless(\App\Support\Acesso::pessoal())
<div class="field"><label for="nascimento">Nascimento</label><input class="input" id="nascimento" name="data_nascimento" type="date" value="{{ old('data_nascimento',substr($aluno['data_nascimento']??'',0,10)) }}" @error('data_nascimento') aria-invalid="true" aria-describedby="erro-data_nascimento" @enderror ><x-erro-campo nome="data_nascimento" /></div>
<div class="field"><label for="responsavel">Responsável</label><input class="input" id="responsavel" name="nome_responsavel" maxlength="200" value="{{ old('nome_responsavel',$aluno['nome_responsavel']??'') }}" @error('nome_responsavel') aria-invalid="true" aria-describedby="erro-nome_responsavel" @enderror ><x-erro-campo nome="nome_responsavel" /></div>
@endunless
</div>
<div class="form-actions"><a class="btn btn-secondary" href="{{ route('turmas.show',['id'=>$aluno['turma_id'],'secao'=>'alunos']) }}"><x-icone nome="fechar" /> Cancelar</a><button class="btn btn-primary" type="submit"><x-icone nome="salvar" /> Salvar alterações</button></div>
</form>
@endsection

@push('scripts')<script src="{{ asset('js/formulario-erros.js') }}" defer></script>@endpush

@push('styles')<link rel="stylesheet" href="{{ asset('css/formulario-escolar.css') }}?v={{ filemtime(public_path('css/formulario-escolar.css')) }}">@endpush
