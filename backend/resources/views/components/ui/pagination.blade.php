@props([
    'paginator',
    'label' => 'Paginacao',
])

@if ($paginator->hasPages())
    <nav aria-label="{{ $label }}" {{ $attributes->class('pagination') }}>
        {{ $paginator->onEachSide(1)->links() }}
    </nav>
@endif
