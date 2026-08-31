@extends('layouts.console')

@section('title', 'Offers — Listora')

@section('content')


<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Operations</span>
        <h1>Inquiries &amp; offers</h1>
        <p>
            {{-- Explains why there are no buttons here, so nobody looks for them. --}}
            Read-only. Responding is the listing owner's decision and lives on their dashboard —
            Listora isn't a party to what they agree.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        <form method="GET" class="filter-form">
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Reference, name, or email…">
            <select name="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}" @selected($filters['status'] === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline btn-sm">Filter</button>
        </form>

        @if ($offers->isEmpty())
            <p class="muted">Nothing matches.</p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Reference</th><th>Listing</th><th>From</th><th>Owner</th><th>Amount</th><th>Status</th><th>Sent</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($offers as $offer)
                            <tr>
                                <td><code>{{ $offer->reference }}</code><br>
                                    <span class="muted">{{ $offer->kind?->label() }}</span></td>
                                <td>
                                    @if ($offer->listing)
                                        <a href="{{ $offer->listing->publicUrl() }}">{{ $offer->listing->title }}</a>
                                    @else <span class="muted">removed</span> @endif
                                </td>
                                <td>{{ $offer->name }}<br><span class="muted">{{ $offer->email }}</span></td>
                                <td class="muted">{{ $offer->owner?->name ?? '—' }}</td>
                                <td>{{ $offer->amount_formatted ?? '—' }}</td>
                                <td>
                                    <span class="pill">{{ $offer->status?->label() }}</span>
                                    @if ($offer->status?->isOpen() && $offer->expires_at)
                                        <br><span class="muted">expires {{ $offer->expires_at->diffForHumans() }}</span>
                                    @endif
                                </td>
                                <td class="muted">{{ $offer->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pager">{{ $offers->links() }}</div>
        @endif
    </div>
</section>

@endsection
