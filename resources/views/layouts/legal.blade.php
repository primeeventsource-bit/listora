<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Legal — Listora')</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset_v('css/listora.css') }}">
<link rel="icon" href="{{ asset('img/favicon.svg') }}" type="image/svg+xml">
</head>
<body>

@include('partials.header', ['overPhoto' => false])

{{--
    `<main class="legal-shell">` is load-bearing, not decorative.

    LegalDocumentRegistry::canonicalText() extracts exactly this region and
    hashes it to produce the version recorded on every terms_acceptance row.
    Renaming or removing the class makes materialiseAll() throw — deliberately,
    rather than silently falling back to hashing the whole page.

    Nothing environment-specific may go inside it: a CSRF token or an absolute
    route() URL would make the same document hash differently per environment,
    and pointing the app at a real domain would mint a new version and force
    every existing user to re-accept terms that had not changed a word.

    The nav bar is not part of the contract. The document is.
--}}
<main class="legal-shell">
    @yield('content')
</main>

@include('partials.footer')

<script src="{{ asset_v('js/listora.js') }}" defer></script>
</body>
</html>
