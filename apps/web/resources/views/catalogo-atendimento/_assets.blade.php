@once
@push('styles')<link rel="stylesheet" href="{{ asset('css/catalogo-cadastro.css') }}?v={{ @filemtime(public_path('css/catalogo-cadastro.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/catalogo-cadastro.js') }}?v={{ @filemtime(public_path('js/catalogo-cadastro.js')) }}"></script>@endpush
@endonce
