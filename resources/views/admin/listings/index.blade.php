@extends('layouts.console')

@section('title', 'Listings — Listora')

@section('content')


<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Operations</span>
        <h1>Listings</h1>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="notice error">{{ $errors->first() }}</div>@endif

        <form method="GET" class="filter-form">
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search title, resort, city…">
            <select name="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}" @selected($filters['status'] === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline btn-sm">Filter</button>
        </form>

        @if ($listings->isEmpty())
            <p class="muted">No listings match.</p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Listing</th><th>Owner</th><th>Plan</th><th>Status</th><th>Views</th><th>Term ends</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($listings as $listing)
                            <tr>
                                <td>
                                    <a href="{{ route('listings.show', $listing) }}">{{ $listing->title }}</a><br>
                                    <span class="muted">{{ $listing->location }}</span>
                                </td>
                                <td>
                                    {{ $listing->owner?->name ?? $listing->owner_name }}<br>
                                    <span class="muted">{{ $listing->owner?->email }}</span>
                                </td>
                                <td>{{ $listing->plan?->label() ?? '—' }}</td>
                                <td>
                                    <span class="pill">{{ $listing->status?->label() }}</span>
                                    @unless ($listing->verified_at)
                                        <br><span class="muted">unverified</span>
                                    @endunless
                                </td>
                                <td>{{ number_format($listing->views) }}</td>
                                <td class="muted">{{ $listing->expires_at?->format('j M Y') ?? '—' }}</td>
                                <td class="row-actions">
                                    <a href="{{ route('admin.listings.edit', $listing) }}" class="btn btn-outline btn-sm">Edit</a>
                                    @if ($listing->status?->isPublic())
                                        <form method="POST" action="{{ route('admin.listings.unpublish', $listing) }}">
                                            @csrf<button class="btn-link">Unpublish</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.listings.publish', $listing) }}">
                                            @csrf<button class="btn-link">Publish</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pager">{{ $listings->links() }}</div>
        @endif
    </div>
</section>

@endsection
