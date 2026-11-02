@extends('layouts.app')
@section('title', $cliente ? 'Cadastro do cliente' : 'Novo cliente')
@section('breadcrumb')<a href="{{ route('clientes.index') }}">Clientes</a><span class="sep">/</span><span>{{ $cliente ? $cliente['nome'] : 'Novo cliente' }}</span>@endsection
@section('content')
<x-pagina :titulo="$cliente ? $cliente['nome'] : 'Novo cliente'">
  <x-slot:sub>{{ $cliente ? 'Situação: '.ucfirst($cliente['situacao']) : 'Preencha os dados do contratante.' }}</x-slot:sub>
</x-pagina>
@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
<form method="POST" action="{{ $cliente ? route('clientes.update',$cliente['id']) : route('clientes.store') }}" class="card card-pad" style="margin-top:20px;">
@csrf @if($cliente) @method('PUT') @endif
<fieldset style="border:0;padding:0;" @disabled(!\App\Support\Acesso::pode('cliente.gerir'))>
<legend>Dados comerciais</legend>
<div class="form-row-2">
  <div class="field"><label for="nome">Nome *</label><input class="input" id="nome" name="nome" required maxlength="200" value="{{ old('nome',$cliente['nome'] ?? '') }}"></div>
  <div class="field"><label for="tipo">Tipo *</label><select class="select" id="tipo" name="tipo" required><option value="">Selecione</option>@foreach($tipos as $v=>$l)<option value="{{ $v }}" @selected(old('tipo',$cliente['tipo'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
  <div class="field"><label for="modalidade">Modalidade *</label><select class="select" id="modalidade" name="modalidade" required><option value="">Selecione</option>@foreach($modalidades as $v=>$l)<option value="{{ $v }}" @selected(old('modalidade',$cliente['modalidade'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
  <div class="field"><label for="documento">Documento</label><input class="input" id="documento" name="documento" maxlength="80" value="{{ old('documento',$cliente['documento'] ?? '') }}"></div>
  <div class="field"><label for="contato_nome">Nome do contato</label><input class="input" id="contato_nome" name="contato_nome" maxlength="200" value="{{ old('contato_nome',$cliente['contato_nome'] ?? '') }}"></div>
  <div class="field"><label for="contato_email">E-mail do contato</label><input class="input" id="contato_email" name="contato_email" type="email" maxlength="254" value="{{ old('contato_email',$cliente['contato_email'] ?? '') }}"></div>
  <div class="field"><label for="contato_telefone">Telefone do contato</label><input class="input" id="contato_telefone" name="contato_telefone" maxlength="40" value="{{ old('contato_telefone',$cliente['contato_telefone'] ?? '') }}"></div>
  <div class="field"><label for="contrato_referencia">Referência contratual</label><input class="input" id="contrato_referencia" name="contrato_referencia" maxlength="120" value="{{ old('contrato_referencia',$cliente['contrato_referencia'] ?? '') }}"><p class="field-help">Identificador do contrato mantido fora deste cadastro.</p></div>
</div>
<p>A modalidade Pagante não gera cobrança automaticamente.</p>
@if(\App\Support\Acesso::pode('cliente.gerir'))<button class="btn btn-primary" type="submit">Salvar cadastro</button>@endif
</fieldset>
</form>
@if($espacos)
<section class="card card-pad" style="margin-top:20px;overflow-x:auto;">
<h2>Espaços do cliente</h2>
<p>{{ $espacos['total'] }} espaço(s). Vínculos agrupados pela situação cadastral.</p>
<table class="table"><thead><tr><th>Espaço</th><th>Tipo</th><th>Situação</th><th>Vínculos</th></tr></thead><tbody>
@forelse($espacos['data'] as $e)
<tr><td>{{ $e['nome'] }}</td><td>{{ ucfirst($e['tipo']) }}</td><td>{{ ucfirst($e['situacao']) }}</td><td>@forelse($e['vinculos'] as $situacao=>$total)<span>{{ ucfirst($situacao) }}: {{ $total }} </span>@empty Nenhum @endforelse</td></tr>
@empty <tr><td colspan="4">Nenhum espaço associado a este cliente.</td></tr> @endforelse
</tbody></table>
<nav aria-label="Paginação dos espaços" style="margin-top:16px;">
@if($espacos['current_page'] > 1)<a class="btn btn-secondary" href="{{ route('clientes.show',['id'=>$cliente['id'],'page'=>$espacos['current_page']-1]) }}">Anterior</a>@endif
<span>Página {{ $espacos['current_page'] }} de {{ $espacos['last_page'] }}</span>
@if($espacos['current_page'] < $espacos['last_page'])<a class="btn btn-secondary" href="{{ route('clientes.show',['id'=>$cliente['id'],'page'=>$espacos['current_page']+1]) }}">Próxima</a>@endif
</nav>
</section>
@endif
@endsection
