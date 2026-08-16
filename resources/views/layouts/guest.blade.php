<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Listora')</title>
<meta name="robots" content="noindex">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/listora.css') }}">
<link rel="icon" href="{{ asset('img/favicon.svg') }}" type="image/svg+xml">
</head>
<body class="auth-body">

{{-- Deliberately no site nav. An auth screen with a full header invites
     people to wander off mid-task; the brand mark links home for anyone who
     landed here by mistake. --}}
<main class="auth-shell">
    <a href="{{ route('home') }}" class="brand auth-brand">
        @include('partials.logo')
        <span class="wordmark">List<span class="o">o</span>ra</span>
    </a>

    <div class="auth-card">
        @yield('content')
    </div>

    <p class="auth-foot muted">
        Need a hand? <a href="{{ route('help.index') }}">Visit the Help centre</a>
        or email <a href="mailto:{{ config('listora.brand.email') }}">{{ config('listora.brand.email') }}</a>.
    </p>
</main>

</body>
</html>
