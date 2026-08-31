{{--
    An advertiser's public advertising page: everything they are currently
    running, at /ad/{member}.

    Public and indexable, unlike the member's own dashboard. This is the
    address a member can put on a card or in a campaign and have it resolve to
    all of their advertising at once, which is why the number is shown on the
    page and not only in the URL.

    Nothing here identifies the advertiser beyond the display name they chose
    and their advertising number. No email, no phone, no account detail -
    contact happens through an inquiry, which is the whole model.
--}}
@extends('layouts.app')

@section('title', $member->name.' — Advertising on Listora')
@section('meta', 'Vacation properties advertised on Listora by '.$member->name.'. Contact the advertiser directly with an inquiry or an offer.')

@section('content')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Advertiser</span>
        <h1>{{ $member->name }}</h1>
        <p class="muted">
            Advertising number <strong>{{ $member->ad_number }}</strong>
            &middot;
            {{ $listings->count() }} {{ Str::plural('property', $listings->count()) }} advertised
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if ($listings->isEmpty())
            <div class="empty">
                <h3>Nothing is being advertised here right now</h3>
                <p>
                    This advertiser has no live listings at the moment. Advertising terms end,
                    and listings come down when they do — nothing has gone wrong.
                </p>
                <p style="margin-top:18px">
                    <a href="{{ route('listings.index') }}" class="btn btn-primary">Browse everything on Listora</a>
                </p>
            </div>
        @else
            <div class="grid-cards">
                @foreach ($listings as $listing)
                    <x-listing-card :listing="$listing" />
                @endforeach
            </div>
        @endif
    </div>
</section>

@endsection
