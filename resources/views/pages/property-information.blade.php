@extends('layouts.app')

@section('title', 'Property information sheet — Listora')
@section('meta', 'Send us the details of what you are advertising and a specialist will contact you to go over your options. No account, no payment, no obligation.')

@section('content')

<div class="page-head plain">
    <div class="wrap">
        <span class="eyebrow">Advertise with Listora</span>
        <h1>Property information sheet</h1>
        <p>
            The short version. Tell us what you hold and how to reach you, and a specialist
            will contact you to go over your options — which plan fits, what we will need to
            verify ownership, and what your listing should say.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-sm">

        @if (session('sheet_reference'))
            <div class="notice">
                <strong>Thank you — we have your sheet.</strong><br>
                Your reference is <code>{{ session('sheet_reference') }}</code>. Quote it if you
                write to us. A specialist will be in touch within one business day, and nothing
                publishes until we have verified ownership with you first.
            </div>
        @endif

        @if ($errors->any())
            <div class="notice error">
                Please check the highlighted fields below.
            </div>
        @endif

        <form method="POST" action="{{ route('property-information.store') }}">
            @csrf

            <div class="field">
                <label for="kind">What are you advertising?</label>
                <select id="kind" name="kind" required>
                    <option value="">Choose one</option>
                    @foreach (\App\Models\Listing::KINDS as $value => $label)
                        <option value="{{ $value }}" @selected(old('kind') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('kind')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="mode">Renting it out, or passing it on?</label>
                <select id="mode" name="mode" required>
                    <option value="">Choose one</option>
                    <option value="rent" @selected(old('mode') === 'rent')>Renting it out</option>
                    <option value="own" @selected(old('mode') === 'own')>Passing on the ownership</option>
                </select>
                @error('mode')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="resort_name">Resort or property name <span style="text-transform:none;font-weight:400">(optional)</span></label>
                <input type="text" id="resort_name" name="resort_name" value="{{ old('resort_name') }}">
                @error('resort_name')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="club_name">Club or collection <span style="text-transform:none;font-weight:400">(optional)</span></label>
                <input type="text" id="club_name" name="club_name" value="{{ old('club_name') }}">
                @error('club_name')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="city">City or area <span style="text-transform:none;font-weight:400">(optional)</span></label>
                <input type="text" id="city" name="city" value="{{ old('city') }}">
                @error('city')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="state">State or country <span style="text-transform:none;font-weight:400">(optional)</span></label>
                <input type="text" id="state" name="state" value="{{ old('state') }}">
                @error('state')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="description">Anything else we should know <span style="text-transform:none;font-weight:400">(optional)</span></label>
                <textarea id="description" name="description"
                          placeholder="Bedrooms, what is included, roughly when it is available — whatever you have to hand. A specialist will fill in the rest with you.">{{ old('description') }}</textarea>
                @error('description')<span class="err">{{ $message }}</span>@enderror
            </div>

            <h2 style="font-size:22px;margin:36px 0 18px">Where should we reach you?</h2>

            <div class="field">
                <label for="owner_name">Your name</label>
                <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" required>
                @error('owner_name')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="owner_email">Email</label>
                <input type="email" id="owner_email" name="owner_email" value="{{ old('owner_email') }}" required>
                @error('owner_email')<span class="err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="phone">Phone <span style="text-transform:none;font-weight:400">(optional)</span></label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
                @error('phone')<span class="err">{{ $message }}</span>@enderror
            </div>

            <button type="submit" class="btn btn-primary btn-lg">Send my information sheet</button>

            <p class="muted" style="margin-top:22px">
                Know exactly what you want already? The
                <a href="{{ route('list.create') }}">full listing form</a> takes about ten
                minutes and lets you write the listing and pick a plan yourself.
            </p>
        </form>

    </div>
</section>

@endsection
