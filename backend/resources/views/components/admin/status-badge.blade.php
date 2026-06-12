@props(['status'])

@php
    $variant = match ($status) {
        'ativo', 'ativa', 'publicada', 'vigente', 'finalizada', 'concluida', 'confirmada', 'correta' => 'success',
        'agendada', 'em_andamento', 'processando', 'validada', 'recebida' => 'info',
        'rascunho', 'previsto', 'pendente', 'requer_revisao', 'com_erros', 'branco', 'dupla' => 'warning',
        'inativo', 'inativa', 'bloqueado', 'cancelada', 'revogado', 'falhou', 'incorreta' => 'danger',
        default => 'neutral',
    };
@endphp

<x-ui.badge :variant="$variant">
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</x-ui.badge>
