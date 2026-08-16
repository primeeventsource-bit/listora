<footer class="site-footer">
    <div class="wrap">
        <div class="f-grid">
            <div>
                <a href="{{ route('home') }}" class="brand">
                    @include('partials.logo', ['tone' => 'light'])
                    <span class="wordmark" style="color:#fff">List<span class="o" style="color:var(--teal-light)">o</span>ra</span>
                </a>
                <p style="max-width:36ch;margin-top:16px">
                    The modern platform to advertise vacation properties, resort club points, and vacation
                    weeks — and connect directly with interested users. One flat fee. No commission, ever.
                </p>
                <div style="margin-top:10px">
                    <a class="store" href="{{ route('apps') }}">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.4 12.8c0-2.3 1.9-3.4 2-3.5-1.1-1.6-2.8-1.8-3.4-1.8-1.4-.1-2.8.9-3.5.9-.7 0-1.8-.8-3-.8-1.5 0-3 .9-3.8 2.3-1.6 2.8-.4 7 1.2 9.3.8 1.1 1.7 2.4 2.9 2.3 1.2 0 1.6-.7 3-.7s1.8.7 3 .7c1.2 0 2-1.1 2.8-2.3.9-1.3 1.2-2.6 1.2-2.6s-2.4-.9-2.4-3.8zM14.2 5.5c.6-.8 1-1.9.9-3-.9 0-2 .6-2.7 1.4-.6.7-1.1 1.8-.9 2.9 1 .1 2.1-.5 2.7-1.3z"/></svg>
                        <span><span class="s1">Download on the</span><span class="s2">App Store</span></span>
                    </a>
                    <a class="store" href="{{ route('apps') }}">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.6 2.3c-.3.3-.5.8-.5 1.4v16.6c0 .6.2 1.1.5 1.4l.1.1 9.3-9.3v-.2L3.6 2.3zM16.3 15.6l-3.1-3.1v-.2l3.1-3.1.1.1 3.7 2.1c1.1.6 1.1 1.6 0 2.2l-3.8 2zM15.9 16.1l-3.2-3.2-9.1 9.1c.4.4 1 .4 1.7 0l10.6-5.9M15.9 8.4L5.3 2.5c-.7-.4-1.3-.4-1.7 0l9.1 9.1 3.2-3.2z"/></svg>
                        <span><span class="s1">Get it on</span><span class="s2">Google Play</span></span>
                    </a>
                </div>
            </div>

            <div>
                <h4>Explore</h4>
                <a href="{{ route('inventory') }}">Inventory</a>
                <a href="{{ route('listings.index', ['kind' => 'home']) }}">Vacation Properties</a>
                <a href="{{ route('listings.index', ['mode' => 'rent']) }}">Available to Rent</a>
                <a href="{{ route('listings.index', ['mode' => 'own']) }}">Available to Own</a>
            </div>

            <div>
                <h4>Advertise</h4>
                <a href="{{ route('list.create') }}">Create a Listing</a>
                <a href="{{ route('pricing') }}">Pricing</a>
                <a href="{{ route('how') }}">How It Works</a>
                <a href="{{ route('apps') }}">Mobile Apps</a>
            </div>

            <div>
                <h4>Company</h4>
                <a href="{{ route('about') }}">About Listora</a>
                <a href="{{ route('about') }}#promise">Our Promise</a>
                <a href="{{ route('help.index') }}">Help &amp; Contact</a>
                <a href="{{ route('about') }}#safety">Safe &amp; Secure</a>
                <a href="{{ route('about') }}#terms">Terms &amp; Privacy</a>
            </div>
        </div>

        @php($brand = config('listora.brand'))

        <div class="f-contact">
            <span>
                <strong>{{ $brand['location']['label'] }}</strong> —
                {{ $brand['location']['country'] }}
            </span>
            <span>
                <a href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a>
            </span>
            <span>
                <a href="{{ route('help.index') }}">Help centre</a>
            </span>
        </div>

        <div class="f-bottom">
            <span>&copy; {{ date('Y') }} Listora. List. Connect. Explore.</span>
            <span>Listora is an advertising platform. We are not a broker and take no commission on any transaction between users.</span>
        </div>
    </div>
</footer>
