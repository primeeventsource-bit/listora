@extends('layouts.app')

@section('title', 'Edit listing — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap-sm">
        <span class="eyebrow">
            <a href="{{ route('owner.listings.index') }}">Your listings</a>
        </span>
        <h1>{{ $listing->title }}</h1>
        <p>{{ $listing->location }} · {{ $listing->kind_label }}</p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-sm">

        @if (session('status'))
            <div class="notice">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('owner.listings.update', $listing) }}" class="stack-form">
            @csrf
            @method('PATCH')

            <div class="field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="{{ old('title', $listing->title) }}"
                       maxlength="160" required>
            </div>

            <div class="field">
                <label for="headline">Headline <span class="muted">(optional)</span></label>
                <input type="text" id="headline" name="headline"
                       value="{{ old('headline', $listing->headline) }}" maxlength="255">
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="10" maxlength="12000" required>{{ old('description', $listing->description) }}</textarea>
                <span class="field-hint">
                    What a buyer actually needs to know — the resort, what the week or points
                    realistically book, and anything ongoing like maintenance fees.
                </span>
            </div>

            <div class="frow">
                <div class="field">
                    <label for="price">Asking price</label>
                    <input type="number" id="price" name="price" step="0.01" min="0"
                           value="{{ old('price', $listing->price) }}" required>
                    <span class="field-hint">Yours to set. Listora never charges or collects it.</span>
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

        <p class="muted">
            {{-- Explains the absence, so nobody hunts for a control that is
                 deliberately not here. --}}
            Your plan, verification status, and term dates aren't editable here — they record
            what our team confirmed and what was agreed commercially.
            <a href="{{ route('help.index') }}#ask">Ask us</a> to change any of them.
        </p>

    </div>
</section>

@endsection
