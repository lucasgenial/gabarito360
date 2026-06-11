@if (session('success'))
    <x-ui.alert variant="success" title="Concluido.">
        {{ session('success') }}
    </x-ui.alert>
@endif

@if ($errors->any())
    <x-ui.alert variant="error" title="Revise os dados informados.">
        Ha campos que precisam de correcao antes de continuar.
    </x-ui.alert>
@endif
