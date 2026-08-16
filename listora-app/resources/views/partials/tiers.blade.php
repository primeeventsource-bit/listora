<div class="tiers">
    @foreach ($plans as $key => $plan)
        <div class="tier reveal {{ ($plan['popular'] ?? false) ? 'hot' : '' }}">
            @if ($plan['popular'] ?? false)
                <span class="flag">Most chosen</span>
            @endif

            <h3>{{ $plan['name'] }}</h3>
            <p class="blurb">{{ $plan['blurb'] }}</p>

            <div class="amt">${{ $plan['price'] }}</div>
            <div class="per">per week listing</div>

            <ul>
                @foreach ($plan['features'] as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>

            <a href="{{ route('list.create', ['plan' => $key]) }}"
               class="btn btn-block {{ ($plan['popular'] ?? false) ? 'btn-amber' : 'btn-primary' }}">
                Choose {{ $plan['name'] }}
            </a>
        </div>
    @endforeach
</div>
