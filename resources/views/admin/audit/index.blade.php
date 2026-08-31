@extends('layouts.console')

@section('title', 'Activity log — Listora')

@section('content')


<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Operations</span>
        <h1>Activity log</h1>
        <p>
            Every privileged change made in the console, who made it, and from where.
            Read-only — an audit trail an admin can edit is not evidence of anything.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">

        <form method="GET" action="{{ route('admin.audit.index') }}" class="filter-form">
            <input type="search" name="q" value="{{ $filters['q'] }}"
                   placeholder="Reference, subject id, or IP" style="min-width:240px">

            <select name="action">
                <option value="">Any action</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                @endforeach
            </select>

            <select name="actor">
                <option value="">Anyone</option>
                @foreach ($actors as $actor)
                    <option value="{{ $actor->id }}" @selected((string) $filters['actor'] === (string) $actor->id)>
                        {{ $actor->name }}
                    </option>
                @endforeach
            </select>

            <select name="days">
                @foreach (['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', 'all' => 'All time'] as $value => $label)
                    <option value="{{ $value }}" @selected((string) $filters['days'] === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-navy btn-sm">Filter</button>
            <a href="{{ route('admin.audit.index') }}" class="btn btn-outline btn-sm">Reset</a>
        </form>

        <div class="toolbar">
            <div class="n">
                <b>{{ number_format($entries->total()) }}</b>
                {{ Str::plural('entry', $entries->total()) }}
                @if ($entries->total() !== $total)
                    of {{ number_format($total) }} recorded
                @endif
            </div>
        </div>

        @if ($entries->isEmpty())
            <div class="empty">
                <h3 style="font-size:24px;margin-bottom:10px">Nothing recorded in this window</h3>
                <p>
                    Widen the date range, or clear the filters. If the log is empty entirely,
                    no privileged change has been made through the console yet.
                </p>
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">When</th>
                            <th scope="col">Who</th>
                            <th scope="col">Action</th>
                            <th scope="col">Subject</th>
                            <th scope="col">From</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td style="white-space:nowrap">
                                    {{ $entry->occurred_at?->format('j M Y') }}
                                    <div class="muted" style="font-size:13px">{{ $entry->occurred_at?->format('H:i:s') }}</div>
                                </td>

                                <td>
                                    {{ $entry->actor?->name ?? 'Deleted user' }}
                                    <div class="muted" style="font-size:13px">{{ $entry->actor?->email }}</div>
                                </td>

                                <td><code>{{ $entry->action }}</code></td>

                                <td>
                                    @if ($entry->subject_type)
                                        {{ class_basename($entry->subject_type) }}
                                        <span class="muted">#{{ $entry->subject_id }}</span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif

                                    {{-- The reference is the handle a person actually
                                         quotes, and it lives in the payload. --}}
                                    @if (is_array($entry->payload) && ! empty($entry->payload['reference']))
                                        <div style="margin-top:4px"><code>{{ $entry->payload['reference'] }}</code></div>
                                    @endif
                                </td>

                                <td class="muted" style="white-space:nowrap">{{ $entry->ip_address ?: '—' }}</td>

                                <td>
                                    <a href="{{ route('admin.audit.show', $entry) }}" class="btn btn-outline btn-sm">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pager">{{ $entries->links() }}</div>
        @endif

    </div>
</section>

@endsection
