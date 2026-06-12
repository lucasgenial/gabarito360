@props([
    'name',
    'label',
    'value' => null,
    'id' => null,
    'help' => null,
    'min' => null,
    'max' => null,
    'wide' => false,
])

@php
    $fieldId = $id ?? $name;
    $errorId = "{$fieldId}-error";
    $helpId = "{$fieldId}-help";
    $errorBag = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag);
    $hasError = $errorBag->has($name);
    $describedBy = collect([$help ? $helpId : null, $hasError ? $errorId : null])->filter()->join(' ');
@endphp

<div class="field {{ $wide ? 'field-wide' : '' }}">
    <label for="{{ $fieldId }}">{{ $label }}</label>
    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="date"
        value="{{ $value }}"
        @if ($min) min="{{ $min }}" @endif
        @if ($max) max="{{ $max }}" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes }}
    >
    @if ($help)
        <span class="help-text" id="{{ $helpId }}">{{ $help }}</span>
    @endif
    @if ($hasError)
        <p class="field-error" id="{{ $errorId }}" role="alert">{{ $errorBag->first($name) }}</p>
    @endif
</div>
