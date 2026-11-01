@extends('layouts.app')
@section('title', 'Gerir vínculo')
@section('content')
<x-pagina titulo="Gerir vínculo"><x-slot:sub>{{ $membro['nome'] }} · {{ $membro['espaco_nome'] }} · {{ \App\Support\Acesso::rotulo($membro['perfil']) }}</x-slot:sub></x-pagina>
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
<section class="card card-pad">
<p>Vínculo #{{ $membro['vinculo_id'] }} · {{ ucfirst($membro['situacao']) }}</p>
<p>{{ $membro['email'] }}</p>
<p>As alterações de acesso valem apenas para este vínculo. A pessoa mantém seus dados de conta e os demais vínculos.</p>
@if(in_array($membro['situacao'], ['ativo', 'suspenso']))
<form method="POST" action="{{ route('membros.update', $membro['id']) }}">
@csrf @method('PUT')
<input type="hidden" name="vinculo_id" value="{{ $membro['vinculo_id'] }}">
<div class="field"><label for="ativo">Acesso neste vínculo</label><select class="select" id="ativo" name="ativo"><option value="1" @selected($membro['situacao'] === 'ativo')>Ativo</option><option value="0" @selected($membro['situacao'] === 'suspenso')>Suspenso</option></select></div>
<button class="btn btn-primary">Salvar acesso</button>
</form>
@else
<p>Este vínculo não pode ser ativado nesta tela. O aceite do convite é feito pela própria pessoa.</p>
@endif
@if($membro['convite_primeiro_acesso'] ?? false)
<form method="POST" action="{{ route('membros.convite', $membro['id']) }}">@csrf<input type="hidden" name="vinculo_id" value="{{ $membro['vinculo_id'] }}"><button class="btn btn-secondary">Reenviar convite de primeiro acesso</button></form>
@endif
<a class="btn btn-secondary" href="{{ route('membros.permissoes', ['id' => $membro['id'], 'vinculo_id' => $membro['vinculo_id']]) }}">Ver permissões</a>
<a class="btn btn-ghost" href="{{ route('membros.index') }}">Voltar à equipe</a>
</section>
@endsection
