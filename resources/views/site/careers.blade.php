@extends('layouts.app')

@section('title', 'Careers — Listora')

@section('content')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Careers</span>
        <h1>Work on something straightforward</h1>
        <p>
            We're a small team building a platform that takes no commission and stays out of
            other people's deals. That constraint shapes everything we make.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if ($openings->isEmpty())
            {{-- An honest empty state. Inventing placeholder roles would waste
                 the time of everyone who applied. --}}
            <div class="section-head">
                <h2>No open roles right now</h2>
                <p class="lead">
                    Nothing is open at the moment, and we'd rather say so than list roles we
                    aren't hiring for. If you think you'd be a fit anyway, write to
                    <a href="mailto:{{ config('listora.brand.email') }}">{{ config('listora.brand.email') }}</a>
                    and tell us what you'd want to work on.
                </p>
            </div>
        @else
            <div class="help-topics">
                @foreach ($openings as $opening)
                    <div class="help-topic">
                        <h3><a href="{{ route('careers.show', $opening) }}">{{ $opening->title }}</a></h3>
                        <p class="muted">
                            {{ $opening->location ?? 'Remote' }}
                            @if ($opening->employment_type) · {{ $opening->employment_type }} @endif
                        </p>
                        @if ($opening->summary)
                            <p>{{ $opening->summary }}</p>
                        @endif
                        <a href="{{ route('careers.show', $opening) }}" class="btn btn-outline btn-sm">Read the role</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endsection
