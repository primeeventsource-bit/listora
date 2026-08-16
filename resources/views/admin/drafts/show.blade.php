@extends('layouts.app')

@section('title', 'Draft '.$draft->reference.' — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow"><a href="{{ route('admin.drafts.index') }}">Review queue</a></span>
        <h1>{{ $draft->title ?: $draft->reference }}</h1>
        <p><code>{{ $draft->reference }}</code> · <span class="pill">{{ $draft->status?->label() }}</span></p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="notice error">{{ $errors->first() }}</div>@endif

        <div class="help-grid">
            <div>
                <div class="section-head"><h2>What was submitted</h2></div>

                <dl class="detail-list">
                    <dt>Owner</dt><dd>{{ $draft->owner_name }} · {{ $draft->owner_email }}
                        @if ($draft->phone) · {{ $draft->phone }} @endif</dd>
                    <dt>Kind</dt><dd>{{ $draft->kind }} · {{ $draft->mode }}</dd>
                    <dt>Resort / club</dt><dd>{{ $draft->resort_name ?: '—' }} / {{ $draft->club_name ?: '—' }}</dd>
                    <dt>Location</dt><dd>{{ collect([$draft->city, $draft->state, $draft->region])->filter()->implode(', ') ?: '—' }}</dd>
                    <dt>Unit</dt><dd>{{ $draft->bedrooms ?? '—' }} bed · sleeps {{ $draft->sleeps ?? '—' }}</dd>
                    <dt>Points</dt><dd>{{ $draft->points ? number_format($draft->points) : '—' }}</dd>
                    <dt>Week</dt><dd>{{ $draft->week_number ?? '—' }} {{ $draft->season ? '· '.$draft->season : '' }}</dd>
                    <dt>Asking price</dt><dd>{{ $draft->price ? '$'.number_format($draft->price) : '—' }} {{ $draft->price_unit }}</dd>
                    <dt>Plan</dt><dd>{{ $draft->plan?->label() ?? '—' }}</dd>
                    <dt>Submitted</dt><dd>{{ $draft->created_at?->format('j M Y, H:i') }}</dd>
                    @if ($draft->verified_at)
                        <dt>Verified</dt>
                        <dd>{{ $draft->verified_at->format('j M Y, H:i') }}
                            @if ($draft->verifiedBy) by {{ $draft->verifiedBy->name }} @endif</dd>
                    @endif
                    @if ($draft->declined_at)
                        <dt>Declined</dt>
                        <dd>{{ $draft->declined_at->format('j M Y, H:i') }} — {{ $draft->decline_reason }}</dd>
                    @endif
                </dl>

                @if ($draft->description)
                    <h3>Description</h3>
                    <article class="prose">{!! nl2br(e($draft->description)) !!}</article>
                @endif
            </div>

            <aside class="help-contact">
                <h3>Decide</h3>

                @if ($draft->listing)
                    <div class="notice">
                        <p>Published as <a href="{{ route('listings.show', $draft->listing) }}">{{ $draft->listing->title }}</a>.</p>
                    </div>
                @else
                    {{-- Verification first, publication second. The publish button is
                         gated on verified_at in ListingPublisher too — this is the
                         affordance, not the enforcement. --}}
                    <form method="POST" action="{{ route('admin.drafts.verify', $draft) }}" class="stack-form">
                        @csrf
                        <div class="field">
                            <label for="note">Verification note <span class="muted">(optional)</span></label>
                            <textarea id="note" name="note" rows="3" maxlength="2000"
                                      placeholder="What documentation did you check?"></textarea>
                        </div>
                        <button class="btn btn-primary btn-block">Mark ownership verified</button>
                    </form>

                    @if ($draft->isReadyToPublish())
                        <form method="POST" action="{{ route('admin.drafts.publish', $draft) }}" style="margin-top:16px">
                            @csrf
                            <button class="btn btn-navy btn-block">Publish as a listing</button>
                        </form>
                    @else
                        <p class="muted" style="margin-top:16px">
                            Publishing unlocks once ownership is verified.
                        </p>
                    @endif

                    <hr>

                    <form method="POST" action="{{ route('admin.drafts.decline', $draft) }}" class="stack-form">
                        @csrf
                        <div class="field">
                            <label for="reason">Decline reason</label>
                            <textarea id="reason" name="reason" rows="3" minlength="10" maxlength="2000" required
                                      placeholder="This is what the owner is told."></textarea>
                            <span class="field-hint">
                                Required — a decline with no reason just becomes a support ticket.
                            </span>
                        </div>
                        <button class="btn btn-outline btn-block">Decline draft</button>
                    </form>
                @endif
            </aside>
        </div>
    </div>
</section>

@endsection
