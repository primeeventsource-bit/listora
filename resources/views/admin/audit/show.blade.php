@extends('layouts.app')

@section('title', 'Activity entry — Listora')
@section('robots', 'noindex, nofollow')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Activity log</span>
        <h1>{{ $entry->action }}</h1>
        <p>{{ $entry->occurred_at?->format('j F Y, H:i:s') }}</p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-md">

        <p style="margin-bottom:26px">
            <a href="{{ route('admin.audit.index') }}" class="btn btn-outline btn-sm">&larr; Back to the log</a>
        </p>

        <dl class="detail-list">
            <dt>Action</dt><dd><code>{{ $entry->action }}</code></dd>

            <dt>Actor</dt>
            <dd>
                @if ($entry->actor)
                    {{ $entry->actor->name }} — {{ $entry->actor->email }}
                    <span class="pill">{{ $entry->actor->role?->label() }}</span>
                @else
                    <span class="muted">The account has since been deleted. The entry stands.</span>
                @endif
            </dd>

            <dt>Subject</dt>
            <dd>
                @if ($entry->subject_type)
                    {{ class_basename($entry->subject_type) }} <span class="muted">#{{ $entry->subject_id }}</span>
                    <div class="muted" style="font-size:13px">{{ $entry->subject_type }}</div>
                @else
                    <span class="muted">None recorded</span>
                @endif
            </dd>

            <dt>When</dt><dd>{{ $entry->occurred_at?->format('j F Y, H:i:s') }} <span class="muted">({{ $entry->occurred_at?->diffForHumans() }})</span></dd>
            <dt>From</dt><dd>{{ $entry->ip_address ?: '—' }}</dd>
        </dl>

        <h2 style="font-size:22px;margin:40px 0 14px">Payload</h2>

        @if (filled($entry->payload))
            {{-- Printed as stored. Whatever the writer recorded is the evidence;
                 prettying it into a summary would be this screen deciding what
                 mattered, months after the fact. --}}
            <pre class="offer-message" style="overflow-x:auto">{{ json_encode($entry->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
        @else
            <p class="muted">Nothing recorded beyond the action itself.</p>
        @endif

        @if ($nearby->isNotEmpty())
            <h2 style="font-size:22px;margin:40px 0 14px">Around the same time</h2>
            <p class="muted" style="margin-bottom:16px">
                What else {{ $entry->actor?->name ?? 'this actor' }} did within thirty minutes either side.
                An audit entry read alone rarely answers the question that opened it.
            </p>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th scope="col">When</th><th scope="col">Action</th><th scope="col">Subject</th><th scope="col"></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($nearby as $other)
                            <tr>
                                <td style="white-space:nowrap">{{ $other->occurred_at?->format('H:i:s') }}</td>
                                <td><code>{{ $other->action }}</code></td>
                                <td>
                                    @if ($other->subject_type)
                                        {{ class_basename($other->subject_type) }} <span class="muted">#{{ $other->subject_id }}</span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td><a href="{{ route('admin.audit.show', $other) }}" class="btn btn-outline btn-sm">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</section>

@endsection
