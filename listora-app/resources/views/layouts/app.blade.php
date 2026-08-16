<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Listora — List. Connect. Explore.')</title>
<meta name="description" content="@yield('meta', 'Listora is the modern platform to advertise vacation properties, resort club points, and vacation weeks — and connect directly with interested users. One flat fee, no commission ever.')">
<meta name="theme-color" content="#009D9A">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://images.unsplash.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/listora.css') }}">
<link rel="icon" href="{{ asset('img/favicon.svg') }}" type="image/svg+xml">
</head>
<body>

@include('partials.header', ['overPhoto' => $overPhoto ?? false])

<main>
    @yield('content')
</main>

@include('partials.footer')

<script src="{{ asset('js/listora.js') }}" defer></script>
@stack('scripts')
</body>
</html>
