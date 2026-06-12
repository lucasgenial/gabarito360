<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'Acesso') | Gabarito360</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="guest-body">
    <div class="guest-theme">
        <x-ui.theme-toggle />
    </div>
    <main class="guest-main">
        @yield('content')
    </main>
    @livewireScripts
</body>
</html>
