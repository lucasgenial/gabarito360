@props([
    'name',
    'label',
    'value' => null,
    'id' => null,
    'help' => null,
    'labelHidden' => false,
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
    <label class="{{ $labelHidden ? 'sr-only' : '' }}" for="{{ $fieldId }}">{{ $label }}</label>
    <textarea
        id="{{ $fieldId }}"
        name="{{ $name }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes }}
    >{{ $value }}</textarea>
    @if ($help)
        <span class="help-text" id="{{ $helpId }}">{{ $help }}</span>
    @endif
    @if ($hasError)
        <p class="field-error" id="{{ $errorId }}" role="alert">{{ $errorBag->first($name) }}</p>
    @endif
</div>
