@extends('layouts.app')
@section('title', 'Convidar para um vínculo')
@section('content')
<x-pagina titulo="Convidar conta existente"><x-slot:sub>A pessoa aceita com sua conta atual, sem criar outra senha.</x-slot:sub></x-pagina>
@if(session('erro'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro">Confira o e-mail, a instituição e a função escolhidos.</x-aviso>@endif
<form method="POST" action="{{ route('membros.convite-vinculo.store') }}" class="card card-pad" style="max-width:720px">
@csrf
<div class="field"><label for="email">E-mail da conta</label><input class="input" type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email"></div>
<div class="field"><label for="espaco">Instituição</label><select class="select" id="espaco" name="espaco_id" required><option value="">Escolha</option>@foreach($opcoes as $o)<option value="{{ $o['id'] }}" @selected(old('espaco_id') == $o['id'])>{{ $o['nome'] }}</option>@endforeach</select></div>
<div class="field"><label for="perfil">Função</label><select class="select" id="perfil" name="perfil" required disabled><option value="">Escolha a instituição</option></select></div>
<fieldset id="turmas" class="field" hidden><legend>Turmas do professor</legend><div id="turmas-opcoes"></div></fieldset>
<p class="field-help">Para reenviar um convite pendente, mantenha a mesma instituição, função e turmas. A resposta não confirma se o e-mail possui uma conta.</p>
<button class="btn btn-primary" type="submit" @disabled(!$opcoes)>Enviar convite</button>
<a class="btn btn-ghost" href="{{ route('membros.index') }}">Cancelar</a>
<noscript><p>Ative o JavaScript para selecionar a instituição e a função.</p></noscript>
</form>
@endsection
@push('scripts')
<script>
const opcoesConvite = @json($opcoes);
const nomesPerfis = @json(collect(\App\Support\Acesso::perfisGeridos())->mapWithKeys(fn($p) => [$p => \App\Support\Acesso::rotulo($p)]));
const espacoConvite = document.getElementById('espaco'), perfilConvite = document.getElementById('perfil');
function turmasConvite() {
 const opcao = opcoesConvite.find(o => String(o.id) === espacoConvite.value);
 const caixa = document.getElementById('turmas-opcoes'); caixa.replaceChildren();
 document.getElementById('turmas').hidden = perfilConvite.value !== 'professor' || !opcao?.turmas.length;
 if (perfilConvite.value !== 'professor') return;
 for (const t of opcao?.turmas || []) { const label = document.createElement('label'), input = document.createElement('input'); input.type = 'checkbox'; input.name = 'turma_ids[]'; input.value = t.id; label.append(input, document.createTextNode(' ' + t.nome)); caixa.append(label, document.createElement('br')); }
}
function perfisConvite() {
 const opcao = opcoesConvite.find(o => String(o.id) === espacoConvite.value);
 perfilConvite.replaceChildren(new Option('Escolha', '')); perfilConvite.disabled = !opcao;
 for (const p of opcao?.perfis || []) perfilConvite.add(new Option(nomesPerfis[p] || p, p));
 turmasConvite();
}
espacoConvite.addEventListener('change', perfisConvite); perfilConvite.addEventListener('change', turmasConvite); perfisConvite();
perfilConvite.value = @json(old('perfil', '')); turmasConvite();
const turmasAnteriores = @json(old('turma_ids', []));
document.querySelectorAll('[name="turma_ids[]"]').forEach(c => c.checked = turmasAnteriores.map(String).includes(c.value));
</script>
@endpush
