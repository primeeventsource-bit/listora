@extends('layouts.app')

@section('title', $opening->title.' — Careers at Listora')

@section('content')

<div class="page-head">
    <div class="wrap-sm">
        <span class="eyebrow"><a href="{{ route('careers.index') }}">Careers</a></span>
        <h1>{{ $opening->title }}</h1>
        <p>
            {{ $opening->location ?? 'Remote' }}
            @if ($opening->employment_type) · {{ $opening->employment_type }} @endif
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-sm">

        @if (session('applied'))
            <div class="notice">
                <p><strong>Application received.</strong> {{ session('applied') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="notice error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <article class="prose">{!! nl2br(e($opening->description)) !!}</article>

        <hr class="rule">

        <h2>Apply</h2>

        <form method="POST" action="{{ route('careers.apply', $opening) }}" class="stack-form">
            @csrf

            <div class="frow">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                </div>
                <div class="field">
                    <label for="last_name">Last name</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                </div>
            </div>

            <div class="frow">
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="field">
                    <label for="phone">Phone <span class="muted">(optional)</span></label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
                </div>
            </div>

            <div class="field">
                <label for="link">Portfolio or LinkedIn <span class="muted">(optional)</span></label>
                <input type="url" id="link" name="link" value="{{ old('link') }}">
            </div>

            <div class="field">
                <label for="message">Why this role?</label>
                <textarea id="message" name="message" rows="7" required>{{ old('message') }}</textarea>
                <span class="field-hint">A few honest paragraphs beat a cover letter template.</span>
            </div>

            <button type="submit" class="btn btn-primary">Send application</button>
        </form>

    </div>
</section>

@endsection
