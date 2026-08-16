@extends('layouts.app')

@section('title', 'Edit '.$listing->title.' — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap-sm">
        <span class="eyebrow"><a href="{{ route('admin.listings.index') }}">Listings</a></span>
        <h1>{{ $listing->title }}</h1>
        <p><span class="pill">{{ $listing->status?->label() }}</span> · {{ $listing->location }}</p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-sm">
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
        @if ($errors->any())
            <div class="notice error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.listings.update', $listing) }}" class="stack-form">
            @csrf
            @method('PATCH')

            <div class="field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="{{ old('title', $listing->title) }}" required maxlength="160">
            </div>

            <div class="field">
                <label for="headline">Headline</label>
                <input type="text" id="headline" name="headline" value="{{ old('headline', $listing->headline) }}" maxlength="255">
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="9" required maxlength="12000">{{ old('description', $listing->description) }}</textarea>
            </div>

            <div class="frow">
                <div class="field">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="{{ old('city', $listing->city) }}" required maxlength="120">
                </div>
                <div class="field">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" value="{{ old('state', $listing->state) }}" maxlength="64">
                </div>
            </div>

            <div class="field">
                <label for="region">Region</label>
                <input type="text" id="region" name="region" value="{{ old('region', $listing->region) }}" required maxlength="96">
            </div>

            <div class="frow">
                <div class="field">
                    <label for="price">Asking price</label>
                    <input type="number" id="price" name="price" step="0.01" min="0"
                           value="{{ old('price', $listing->price) }}" required>
                </div>
                <div class="field">
                    <label for="price_unit">Per</label>
                    <select id="price_unit" name="price_unit" required>
                        @foreach (['total' => 'Total', 'night' => 'Night', 'week' => 'Week', 'point' => 'Point'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('price_unit', $listing->price_unit) === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save changes</button>
        </form>

        <hr class="rule">

        <h2>Advertising plan</h2>
        <p class="muted">
            The plan sets term length, photo allowance, and placement. Nothing is charged here —
            plans are arranged with the owner directly.
        </p>

        <form method="POST" action="{{ route('admin.listings.plan', $listing) }}" class="stack-form">
            @csrf

            <div class="field">
                <label for="plan">Plan</label>
                <select id="plan" name="plan" required>
                    @foreach ($plans as $value => $label)
                        <option value="{{ $value }}" @selected($listing->plan?->value === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <label class="checkline">
                <input type="checkbox" name="extend_term" value="1">
                <span>Restart the advertising term from today</span>
            </label>

            <button type="submit" class="btn btn-outline">Update plan</button>
        </form>
    </div>
</section>

@endsection
