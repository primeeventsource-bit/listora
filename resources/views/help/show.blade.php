@extends('layouts.app')

@section('title', $article->title.' — Listora Help')
@section('meta', $article->summary)

@php($brand = config('listora.brand'))

@section('content')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">
            <a href="{{ route('help.index') }}">Help</a> ·
            {{ Str::headline($article->category) }}
        </span>
        <h1>{{ $article->title }}</h1>
        <p>{{ $article->summary }}</p>
    </div>
</div>

<section>
    <div class="wrap-sm">
        <article class="prose reveal">
            {{-- Article bodies are written by our own staff through the admin
                 console, but they are still stored content rendered on a public
                 page — escaped, so a pasted fragment of HTML shows as text
                 rather than executing. nl2br keeps the paragraphing. --}}
            {!! nl2br(e($article->body)) !!}
        </article>

        <div class="article-foot reveal">
            <h3>Did this answer it?</h3>
            <p class="muted">
                If not, ask our assistant on the <a href="{{ route('help.index') }}">Help page</a> — it answers
                straight away — or <a href="{{ route('help.index') }}#ask">write to us</a> and a person will
                reply {{ $brand['response_time'] }}. You can also email
                <a href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a>.
            </p>
        </div>
    </div>
</section>

@endsection
