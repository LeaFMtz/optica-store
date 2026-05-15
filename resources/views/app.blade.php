<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Óptica Guzmán</title>
    <meta name="description" content="Tus lentes hechos a tu medida.">

    <link rel="icon" href="{{ asset('favicon.ico') }}">

    @routes
    @inertiaHead
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased bg-gray-50 text-gray-900 flex flex-col min-h-screen">
    @inertia
</body>

</html>
