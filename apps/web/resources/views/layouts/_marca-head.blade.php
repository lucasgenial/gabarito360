@php($social = \App\Support\CompartilhamentoPublico::metadados())
<link rel="canonical" href="{{ $social['url'] }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" href="{{ asset('favicon-16x16.png') }}" sizes="16x16" type="image/png">
<link rel="icon" href="{{ asset('favicon-32x32.png') }}" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}" sizes="180x180">
{{-- use-credentials: a homologação exige Basic Auth também no manifesto (docs/198) --}}
<link rel="manifest" href="{{ asset('site.webmanifest') }}" crossorigin="use-credentials">
<meta name="theme-color" content="#001534">
<meta name="application-name" content="Gabarito360">
<meta name="apple-mobile-web-app-title" content="Gabarito360">
<meta property="og:site_name" content="Gabarito360">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $social['url'] }}">
<meta property="og:locale" content="pt_BR">
<meta property="og:title" content="{{ $social['titulo'] }}">
<meta property="og:description" content="{{ $social['descricao'] }}">
<meta property="og:image" content="{{ asset('brand/gabarito360-og.png') }}">
@if(str_starts_with(asset('brand/gabarito360-og.png'), 'https://'))<meta property="og:image:secure_url" content="{{ asset('brand/gabarito360-og.png') }}">@endif
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Marca Gabarito360 em branco e azul sobre fundo azul-marinho. Correção de provas e relatórios educacionais.">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $social['titulo'] }}">
<meta name="twitter:description" content="{{ $social['descricao'] }}">
<meta name="twitter:image" content="{{ asset('brand/gabarito360-og.png') }}">
<meta name="twitter:image:alt" content="Marca Gabarito360 em branco e azul sobre fundo azul-marinho. Correção de provas e relatórios educacionais.">
<link rel="stylesheet" href="@ativo('css/marca.css')">
