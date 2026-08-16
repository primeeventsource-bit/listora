@extends('layouts.app')

@section('title', 'Press — Listora')

@section('content')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Press</span>
        <h1>Press and media</h1>
        <p>
            For interviews, background, or anything you need checked before publication, write to
            <a href="mailto:{{ $mediaEmail }}">{{ $mediaEmail }}</a>.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if ($releases->isEmpty())
            <div class="section-head">
                <h2>Nothing published yet</h2>
                <p class="lead">
                    We haven't issued a release. When we do it will be here.
                    In the meantime, <a href="mailto:{{ $mediaEmail }}">{{ $mediaEmail }}</a>
                    reaches a person.
                </p>
            </div>
        @else
            <div class="help-topics">
                @foreach ($releases as $release)
                    <div class="help-topic">
                        <h3><a href="{{ route('press.show', $release) }}">{{ $release->title }}</a></h3>
                        <p class="muted">{{ $release->published_at?->format('j F Y') }}</p>
                        @if ($release->summary)<p>{{ $release->summary }}</p>@endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endsection
