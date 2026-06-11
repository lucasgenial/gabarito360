@props(['status'])

<x-ui.badge :variant="$status === 'ativo' || $status === 'publicada' ? 'success' : 'neutral'">
    {{ ucfirst($status) }}
</x-ui.badge>
