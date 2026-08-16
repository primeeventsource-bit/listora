@extends('layouts.app')

@section('title', 'Please review our terms — Listora')

@section('content')

<div class="page-head">
    <div class="wrap-sm">
        <span class="eyebrow">Legal</span>
        <h1>We've updated our terms</h1>
        <p>
            Please review what changed and accept to carry on. It takes a minute, and you only
            see this when a document has genuinely changed.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-sm">
        @if (empty($missing))
            <div class="notice">
                <p>You're up to date — nothing to accept.</p>
                <p><a href="{{ route('dashboard') }}">Back to your dashboard</a></p>
            </div>
        @else
            <ul class="accept-list">
                @foreach ($missing as $version)
                    <li>
                        <a href="{{ $version->publicUrl() }}" target="_blank" rel="noopener">
                            {{ Str::headline($version->kind) }}
                        </a>
                        <span class="muted">
                            {{ $version->version_label }} · effective
                            {{ $version->effective_at?->format('j F Y') }}
                        </span>
                    </li>
                @endforeach
            </ul>

            <form method="POST" action="{{ route('legal.review-and-accept.submit') }}" class="accept-form">
                @csrf

                <label class="checkline">
                    <input type="checkbox" name="confirm" required>
                    <span>I have read and accept the documents listed above.</span>
                </label>

                <button type="submit" class="btn btn-primary">Accept and continue</button>
            </form>

            <p class="muted" style="margin-top:22px">
                {{-- Said plainly: an account they cannot leave is worse than one they
                     choose to close. --}}
                Not comfortable accepting? You can
                <a href="{{ route('help.index') }}#ask">talk to us</a> first, or sign out and come
                back later. Your listings stay exactly as they are in the meantime.
            </p>
        @endif
    </div>
</section>

@endsection
