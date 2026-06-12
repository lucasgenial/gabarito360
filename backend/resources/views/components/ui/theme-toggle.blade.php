<button
    type="button"
    data-theme-toggle
    aria-label="Ativar tema escuro"
    title="Ativar tema escuro"
    {{ $attributes->class('theme-toggle') }}
>
    <svg data-theme-icon="dark" aria-hidden="true" viewBox="0 0 24 24">
        <path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"></path>
    </svg>
    <svg data-theme-icon="light" aria-hidden="true" viewBox="0 0 24 24" hidden>
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path>
    </svg>
</button>
