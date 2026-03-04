<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Zewalo - Platform manajemen rental terbaik. Kelola bisnis penyewaan Anda dalam hitungan menit.">
    <title>{{ $title ?? 'Zewalo - Platform Rental Management' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700,800,900|inter:300,400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-white text-gray-900">
    @hasSection('content')
        @yield('content')
    @else
        {{ $slot }}
    @endif
    @livewireScripts
</body>
</html>
