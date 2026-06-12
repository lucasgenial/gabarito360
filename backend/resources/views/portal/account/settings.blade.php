@extends('layouts.admin')

@section('title', 'Configuracoes')

@section('content')
    @php($preferences = $user->preferencia)
    <header class="page-heading">
        <p class="eyebrow">Conta</p>
        <h1>Configuracoes</h1>
        <p>Preferencias de aparencia e seguranca da sua conta.</p>
    </header>

    <div class="content-grid">
        <x-ui.card labelledby="preferencias-interface">
            <h2 id="preferencias-interface">Aparencia e acessibilidade</h2>
            <form class="form-grid" method="POST" action="{{ route('portal.settings.preferences') }}">
                @csrf
                @method('PATCH')
                <x-ui.select name="tema" label="Tema" required>
                    <option value="claro" @selected(old('tema', $preferences?->tema ?? 'claro') === 'claro')>Claro</option>
                    <option value="escuro" @selected(old('tema', $preferences?->tema) === 'escuro')>Escuro</option>
                </x-ui.select>
                <x-ui.select name="densidade" label="Densidade" required>
                    <option value="confortavel" @selected(old('densidade', $preferences?->densidade ?? 'confortavel') === 'confortavel')>Confortavel</option>
                    <option value="compacta" @selected(old('densidade', $preferences?->densidade) === 'compacta')>Compacta</option>
                </x-ui.select>
                <x-ui.select name="idioma" label="Idioma" required>
                    <option value="pt-BR">Portugues do Brasil</option>
                </x-ui.select>
                <label class="check-field"><input type="checkbox" name="contraste_alto" value="1" @checked(old('contraste_alto', $preferences?->contraste_alto))> Contraste alto</label>
                <label class="check-field"><input type="checkbox" name="reduzir_movimento" value="1" @checked(old('reduzir_movimento', $preferences?->reduzir_movimento))> Reduzir movimento</label>
                <div class="form-actions field-wide">
                    <x-ui.button type="submit">Salvar preferencias</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card labelledby="seguranca-conta">
            <h2 id="seguranca-conta">Alterar senha</h2>
            <p class="help-text">A alteracao revoga os tokens de API existentes.</p>
            <form class="form-grid" method="POST" action="{{ route('portal.settings.password') }}">
                @csrf
                @method('PATCH')
                <x-ui.input name="current_password" label="Senha atual" type="password" required autocomplete="current-password" />
                <x-ui.input name="password" label="Nova senha" type="password" required minlength="12" autocomplete="new-password" />
                <x-ui.input name="password_confirmation" label="Confirmar nova senha" type="password" required minlength="12" autocomplete="new-password" />
                <div class="form-actions field-wide">
                    <x-ui.button type="submit">Atualizar senha</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>

    <x-ui.alert title="Escopo desta etapa">
        Integracoes externas e automacoes de notificacao permanecem indisponiveis ate aprovacao especifica.
    </x-ui.alert>
@endsection
