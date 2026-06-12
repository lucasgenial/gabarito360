@props([
    'name',
    'context' => 'Usuario autenticado',
])

<details {{ $attributes->class('account-menu') }}>
    <summary aria-label="Abrir menu da conta">
        <x-ui.avatar :name="$name" size="sm" />
        <span class="account-menu-label">
            <strong>{{ $name }}</strong>
            <span>{{ $context }}</span>
        </span>
    </summary>
    <div class="account-menu-panel">
        <div class="account-menu-header">
            <strong>{{ $name }}</strong>
            <span>{{ $context }}</span>
        </div>
        {{ $slot }}
    </div>
</details>
