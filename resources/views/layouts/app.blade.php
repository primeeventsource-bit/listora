<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Listora — List. Connect. Explore.')</title>
<meta name="description" content="@yield('meta', 'Listora is the modern platform to advertise vacation properties, resort club points, and vacation weeks — and connect directly with interested users. One flat fee, no commission ever.')">
<meta name="theme-color" content="#009D9A">

{{--
    Indexing. `seo.robots_index` is the site-wide kill switch in Settings → SEO;
    a page may narrow it via @section('robots') but must not widen it, so any
    page-level value is only consulted while indexing is allowed at all.
--}}
@php($indexingAllowed = (bool) setting('seo.robots_index', true))
<meta name="robots" content="{{ $indexingAllowed ? trim($__env->yieldContent('robots', 'index, follow')) : 'noindex, follow' }}">

{{-- Per-page canonical, Open Graph, and JSON-LD.
     `@yield` for pages that define one block; `@stack` for partials and
     components that need to contribute a tag without owning the section. --}}
@yield('head')
@stack('head')

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://images.unsplash.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/listora.css') }}">
<link rel="icon" href="{{ asset('img/favicon.svg') }}" type="image/svg+xml">

@include('partials.analytics', ['visitorId' => request()->attributes->get('listora_visitor_id')])
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
