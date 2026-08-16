@extends('layouts.app')

@section('title', $release->title.' — Listora Press')

@section('content')

<div class="page-head">
    <div class="wrap-sm">
        <span class="eyebrow"><a href="{{ route('press.index') }}">Press</a></span>
        <h1>{{ $release->title }}</h1>
        <p>{{ $release->published_at?->format('j F Y') }}</p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-sm">
        <article class="prose">{!! nl2br(e($release->body)) !!}</article>

        <hr class="rule">

        <p class="muted">
            Media enquiries: <a href="mailto:{{ $mediaEmail }}">{{ $mediaEmail }}</a>
        </p>
    </div>
</section>

@endsection
