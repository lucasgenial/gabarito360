@push('styles')<link rel="stylesheet" href="{{ asset('css/disciplinas.css') }}?v={{ @filemtime(public_path('css/disciplinas.css')) }}">@endpush
@include('disciplinas._confirmacao')
@if(session('sucesso'))<x-aviso tipo="sucesso">{{ session('sucesso') }}</x-aviso>@endif
@if(session('erro') && !$errors->any() && !session('conflito_dc1'))<x-aviso tipo="erro">{{ session('erro') }}</x-aviso>@endif
@if($errors->any())<x-aviso tipo="erro"><ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></x-aviso>@endif
@if(session('conflito_dc1'))<x-aviso tipo="erro">Os dados mudaram. Seu preenchimento será mantido ao atualizar; confira o estado atual antes de confirmar novamente. <a href="{{ request()->fullUrlWithQuery(['rever'=>1]) }}">Atualizar estado e manter preenchimento</a></x-aviso>@endif
@push('scripts')<script src="{{ asset('js/disciplinas.js') }}?v={{ @filemtime(public_path('js/disciplinas.js')) }}"></script>@endpush
