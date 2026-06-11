@props(['status'])

<span class="badge {{ $status === 'ativo' ? 'badge-success' : 'badge-neutral' }}">
    {{ ucfirst($status) }}
</span>
