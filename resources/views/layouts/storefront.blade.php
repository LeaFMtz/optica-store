<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Óptica Guzmán</title>
    <meta name="description" content="Tus lentes hechos a tu medida.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="{{ asset('favicon.png') }}">

    @livewireStyles
    @stripeScripts
</head>

<body class="antialiased bg-gray-50 text-gray-900 flex flex-col min-h-screen">

    @livewire('components.navigation')

    <main class="flex-1">
        {{ $slot }}
    </main>

    <x-footer />

    @livewireScripts
</body>

</html>
