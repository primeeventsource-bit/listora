@extends('layouts.app')

@section('title', 'List your property — Listora')

@section('content')

<div class="page-head plain">
    <div class="wrap">
        <span class="eyebrow">Create your listing</span>
        <h1>Ten minutes now, twelve months of visibility</h1>
        <p>Tell us what you hold and how you want to advertise it. Nothing is charged until you have seen exactly how your listing will look.</p>
    </div>
</div>

<section>
    <div class="wrap-md">

        @if (session('draft'))
            <div class="notice">
                <strong>Your listing is with our verification team.</strong><br>
                Reference {{ session('draft') }} on the {{ session('plan') }} plan. We'll email you within two
                business days once your ownership documents are checked — and nothing is charged until it publishes.
            </div>
        @endif

        @if ($errors->any())
            <div class="notice" style="background:rgba(196,99,74,.1);border-color:rgba(196,99,74,.4);color:#8A3A26">
                Please check the highlighted fields below.
            </div>
        @endif

        <div class="stepper" id="stepper">
            <div class="s on">1 &nbsp;What you hold</div>
            <div class="s">2 &nbsp;The details</div>
            <div class="s">3 &nbsp;Your plan</div>
            <div class="s">4 &nbsp;Contact</div>
        </div>

        <form method="POST" action="{{ route('list.store') }}" id="wizard">
            @csrf

            {{-- ---------------------------- step 1 ---------------------------- --}}
            <div class="wiz-step on" data-step="1">
                <h2 style="font-size:30px;margin-bottom:10px">What are you advertising?</h2>
                <p class="muted" style="margin-bottom:28px">Each type has its own fields, because a beach house and a points balance need to be described very differently.</p>

                <div class="choices" id="kindChoices">
                    <button type="button" class="choice {{ old('kind', 'home') === 'home' ? 'on' : '' }}" data-kind="home">
                        <h4>Vacation Home or Villa</h4>
                        <p>A house, condo, cabin, or villa that you own outright.</p>
                    </button>
                    <button type="button" class="choice {{ old('kind') === 'points' ? 'on' : '' }}" data-kind="points">
                        <h4>Resort Club Points</h4>
                        <p>A points balance in a club or collection you belong to.</p>
                    </button>
                    <button type="button" class="choice {{ old('kind') === 'weeks' ? 'on' : '' }}" data-kind="weeks">
                        <h4>Vacation Week</h4>
                        <p>A fixed or floating week at a resort you own at.</p>
                    </button>
                </div>
                <input type="hidden" name="kind" id="kindInput" value="{{ old('kind', 'home') }}">

                <h2 style="font-size:30px;margin:44px 0 10px">Renting it out, or passing it on?</h2>
                <p class="muted" style="margin-bottom:28px">You can change this later, and you can run separate listings for each.</p>

                <div class="choices" id="modeChoices" style="grid-template-columns:1fr 1fr">
                    <button type="button" class="choice {{ old('mode', 'rent') === 'rent' ? 'on' : '' }}" data-mode="rent">
                        <h4>Available to rent</h4>
                        <p>Someone stays; you keep the ownership.</p>
                    </button>
                    <button type="button" class="choice {{ old('mode') === 'own' ? 'on' : '' }}" data-mode="own">
                        <h4>Available to own</h4>
                        <p>You're transferring or selling it permanently.</p>
                    </button>
                </div>
                <input type="hidden" name="mode" id="modeInput" value="{{ old('mode', 'rent') }}">

                <div style="margin-top:44px;display:flex;justify-content:flex-end">
                    <button type="button" class="btn btn-primary btn-lg" data-next>Continue</button>
                </div>
            </div>

            {{-- ---------------------------- step 2 ---------------------------- --}}
            <div class="wiz-step" data-step="2">
                <h2 style="font-size:30px;margin-bottom:10px">The details</h2>
                <p class="muted" style="margin-bottom:28px">Write it the way you'd describe it to a friend. Owners' own words are the reason people trust listings here.</p>

                <div class="frow">
                    <div class="field">
                        <label for="resort_name">Resort or property name</label>
                        <input type="text" id="resort_name" name="resort_name" value="{{ old('resort_name') }}" placeholder="Kaanapali Shores">
                    </div>
                    <div class="field js-club">
                        <label for="club_name">Club or collection</label>
                        <input type="text" id="club_name" name="club_name" value="{{ old('club_name') }}" placeholder="Coral Cay Club">
                    </div>
                </div>

                <div class="frow">
                    <div class="field">
                        <label for="city">City or area</label>
                        <input type="text" id="city" name="city" value="{{ old('city') }}" placeholder="Lahaina">
                    </div>
                    <div class="field">
                        <label for="state">State / country</label>
                        <input type="text" id="state" name="state" value="{{ old('state') }}" placeholder="HI">
                    </div>
                </div>

                <div class="field">
                    <label for="title">Listing headline</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" placeholder="Week 26 oceanfront at Kaanapali Shores">
                </div>

                {{-- home fields --}}
                <div class="js-only js-home">
                    <div class="frow">
                        <div class="field"><label for="bedrooms">Bedrooms</label><input type="number" id="bedrooms" name="bedrooms" min="0" max="20" value="{{ old('bedrooms') }}"></div>
                        <div class="field"><label for="sleeps">Sleeps</label><input type="number" id="sleeps" name="sleeps" min="1" max="40" value="{{ old('sleeps') }}"></div>
                    </div>
                </div>

                {{-- points fields --}}
                <div class="js-only js-points" hidden>
                    <div class="frow">
                        <div class="field"><label for="points">Points balance</label><input type="number" id="points" name="points" min="1" value="{{ old('points') }}" placeholder="3750"></div>
                        <div class="field"><label for="season_p">Season</label><input type="text" id="season_p" name="season" value="{{ old('season') }}" placeholder="Platinum"></div>
                    </div>
                </div>

                {{-- weeks fields --}}
                <div class="js-only js-weeks" hidden>
                    <div class="frow">
                        <div class="field"><label for="week_number">Week number</label><input type="number" id="week_number" name="week_number" min="1" max="53" value="{{ old('week_number') }}" placeholder="26"></div>
                        <div class="field"><label for="season_w">Season</label><input type="text" id="season_w" name="season" value="{{ old('season') }}" placeholder="Platinum"></div>
                    </div>
                </div>

                <div class="frow">
                    <div class="field">
                        <label for="price">Asking price (USD)</label>
                        <input type="number" id="price" name="price" min="0" value="{{ old('price') }}" placeholder="4650">
                    </div>
                    <div class="field">
                        <label for="price_unit">Priced per</label>
                        <select id="price_unit" name="price_unit">
                            <option value="week"  @selected(old('price_unit') === 'week')>Week</option>
                            <option value="night" @selected(old('price_unit') === 'night')>Night</option>
                            <option value="point" @selected(old('price_unit') === 'point')>Point</option>
                            <option value="total" @selected(old('price_unit') === 'total')>Total (transfer price)</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="description">Describe it</label>
                    <textarea id="description" name="description" placeholder="What makes it worth the trip? Which weeks are best? What do you leave for guests?">{{ old('description') }}</textarea>
                </div>

                <div style="margin-top:32px;display:flex;justify-content:space-between;gap:14px">
                    <button type="button" class="btn btn-outline btn-lg" data-back>Back</button>
                    <button type="button" class="btn btn-primary btn-lg" data-next>Continue</button>
                </div>
            </div>

            {{-- ---------------------------- step 3 ---------------------------- --}}
            <div class="wiz-step" data-step="3">
                <h2 style="font-size:30px;margin-bottom:10px">Choose your plan</h2>
                <p class="muted" style="margin-bottom:32px">One flat fee for twelve months. No commission is taken from your deal on any plan — the difference is only how visible your listing is.</p>

                <div class="choices" id="planChoices">
                    @foreach ($plans as $key => $plan)
                        <button type="button" class="choice {{ old('plan', request('plan', 'featured')) === $key ? 'on' : '' }}" data-plan="{{ $key }}">
                            <h4>{{ $plan['name'] }}</h4>
                            <div style="font-family:var(--font);font-size:34px;margin:8px 0 10px">${{ $plan['price'] }}</div>
                            <p>{{ $plan['blurb'] }}</p>
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="plan" id="planInput" value="{{ old('plan', request('plan', 'featured')) }}">

                <div style="margin-top:32px;display:flex;justify-content:space-between;gap:14px">
                    <button type="button" class="btn btn-outline btn-lg" data-back>Back</button>
                    <button type="button" class="btn btn-primary btn-lg" data-next>Continue</button>
                </div>
            </div>

            {{-- ---------------------------- step 4 ---------------------------- --}}
            <div class="wiz-step" data-step="4">
                <h2 style="font-size:30px;margin-bottom:10px">Where should we reach you?</h2>
                <p class="muted" style="margin-bottom:28px">Only our verification team sees this. Your email is never shown on the listing and never sold.</p>

                <div class="frow">
                    <div class="field">
                        <label for="owner_name">Your name</label>
                        <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" required>
                        @error('owner_name') <span class="err">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label for="phone">Phone (optional)</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
                    </div>
                </div>

                <div class="field">
                    <label for="owner_email">Email</label>
                    <input type="email" id="owner_email" name="owner_email" value="{{ old('owner_email') }}" required>
                    @error('owner_email') <span class="err">{{ $message }}</span> @enderror
                </div>

                <div class="notice" style="margin-top:28px">
                    <strong>What happens next.</strong> We'll email you a secure link to upload your deed, club
                    statement, or membership certificate. Our team checks it against the details you entered and
                    replies within two business days. Nothing publishes — and nothing is charged — until you approve it.
                </div>

                <div style="margin-top:32px;display:flex;justify-content:space-between;gap:14px">
                    <button type="button" class="btn btn-outline btn-lg" data-back>Back</button>
                    <button type="submit" class="btn btn-amber btn-lg">Submit for verification</button>
                </div>
            </div>
        </form>
    </div>
</section>

@endsection
