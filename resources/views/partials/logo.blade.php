@php
    // $tone: 'color' (default), 'light' (for dark backgrounds), 'mono'
    $tone   = $tone ?? 'color';
    $navy   = $tone === 'light' ? '#FFFFFF' : '#0D1B2A';
    $teal   = $tone === 'light' ? '#4CC6C6' : '#009D9A';
    $teal2  = $tone === 'light' ? '#FFFFFF' : '#4CC6C6';
    $sun    = $tone === 'light' ? '#FFB545' : '#FFB545';
@endphp

<svg class="logo-mark" viewBox="0 0 100 100" fill="none" aria-hidden="true">
    {{-- open ring --}}
    <path d="M91.3 35.0 A44 44 0 1 1 65.0 8.7" stroke="{{ $teal }}" stroke-width="6.5" stroke-linecap="round"/>
    {{-- sun in the gap --}}
    <circle cx="82" cy="19" r="7.4" fill="{{ $sun }}"/>

    {{-- palm --}}
    <path d="M35.5 68 C34 58 33 50 31.5 41.5" stroke="{{ $navy }}" stroke-width="4" stroke-linecap="round"/>
    <path d="M31.5 40.5 C25 33 19.5 33.5 16 37" stroke="{{ $navy }}" stroke-width="3.6" stroke-linecap="round"/>
    <path d="M31.5 40.5 C25.5 30.5 27 25 30.5 22" stroke="{{ $navy }}" stroke-width="3.6" stroke-linecap="round"/>
    <path d="M31.5 40.5 C38.5 32.5 44 33 47 36.5" stroke="{{ $navy }}" stroke-width="3.6" stroke-linecap="round"/>
    <path d="M31.5 40.5 C39 36 43.5 38.5 45.5 43" stroke="{{ $navy }}" stroke-width="3.6" stroke-linecap="round"/>
    <path d="M31.5 40.5 C24 37.5 19.5 40.5 18 45" stroke="{{ $navy }}" stroke-width="3.6" stroke-linecap="round"/>

    {{-- house --}}
    <path d="M50 52.5 L63.5 42 L77 52.5" stroke="{{ $navy }}" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M54 51.5 V68 H73 V51.5" stroke="{{ $navy }}" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round"/>
    <rect x="60" y="57.5" width="7.5" height="10.5" rx="1" fill="{{ $navy }}"/>

    {{-- waves --}}
    <path d="M18 74 C24 70.5 29 77.5 35 74 C41 70.5 46 77.5 52 74" stroke="{{ $teal }}" stroke-width="4" stroke-linecap="round"/>
    <path d="M32 83 C39 79 45.5 86 52.5 82 C59.5 78 66 85 73 81" stroke="{{ $teal2 }}" stroke-width="4" stroke-linecap="round"/>
</svg>
