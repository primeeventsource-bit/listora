@extends('layouts.console')

@section('title', 'Inbox — Listora')

@section('content')


<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Operations</span>
        <h1>Inbox</h1>
        <p>Everything the public forms produce. Without this they'd be write-only.</p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if (session('success'))<div class="notice">{{ session('success') }}</div>@endif

        <div class="filter-bar">
            <a href="{{ route('admin.inbox.index', ['tab' => 'contact']) }}"
               class="chip {{ $tab === 'contact' ? 'is-active' : '' }}">Questions ({{ $counts['contact'] }})</a>
            <a href="{{ route('admin.inbox.index', ['tab' => 'support']) }}"
               class="chip {{ $tab === 'support' ? 'is-active' : '' }}">Support ({{ $counts['support'] }})</a>
            <a href="{{ route('admin.inbox.index', ['tab' => 'applications']) }}"
               class="chip {{ $tab === 'applications' ? 'is-active' : '' }}">Applications ({{ $counts['applications'] }})</a>
        </div>

        {{-- Listing drafts are deliberately absent: they have their own queue,
             with verification attached. See Admin\DraftController. --}}
        <p class="muted" style="margin-bottom:24px">
            Looking for owners wanting to advertise? They're in the
            <a href="{{ route('admin.drafts.index') }}">review queue</a>, not here.
        </p>

        @if ($tab === 'contact' && $contactMessages)
            @if ($contactMessages->isEmpty())
                <p class="muted">Nothing waiting.</p>
            @else
                <div class="offer-list">
                    @foreach ($contactMessages as $message)
                        <article class="offer-card">
                            <header>
                                <div>
                                    <code>{{ $message->reference }}</code>
                                    <span class="pill">{{ $message->department?->label() }}</span>
                                    <span class="pill">{{ $message->status }}</span>
                                </div>
                                <span class="muted">{{ $message->created_at?->diffForHumans() }}</span>
                            </header>

                            <h3>{{ $message->subject }}</h3>
                            <p class="muted">
                                {{ $message->first_name }} {{ $message->last_name }} ·
                                <a href="mailto:{{ $message->email }}">{{ $message->email }}</a>
                                @if ($message->phone) · {{ $message->phone }} @endif
                            </p>
                            <p class="offer-message">{{ $message->message }}</p>

                            <footer>
                                <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject.' ['.$message->reference.']') }}"
                                   class="btn btn-primary btn-sm">Reply</a>

                                @if ($message->status !== 'handled')
                                    <form method="POST" action="{{ route('admin.inbox.handled', $message) }}">
                                        @csrf<button class="btn btn-outline btn-sm">Mark handled</button>
                                    </form>
                                @endif
                            </footer>
                        </article>
                    @endforeach
                </div>
                <div class="pager">{{ $contactMessages->links() }}</div>
            @endif

        @elseif ($tab === 'support' && $tickets)
            @if ($tickets->isEmpty())
                <p class="muted">No open tickets.</p>
            @else
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Subject</th><th>Opened by</th><th>Priority</th><th>Status</th><th>When</th></tr></thead>
                        <tbody>
                            @foreach ($tickets as $ticket)
                                <tr>
                                    <td>{{ $ticket->subject }}</td>
                                    <td class="muted">{{ $ticket->openedBy?->email ?? 'anonymous' }}</td>
                                    <td>{{ $ticket->priority }}</td>
                                    <td><span class="pill">{{ $ticket->status }}</span></td>
                                    <td class="muted">{{ $ticket->created_at?->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pager">{{ $tickets->links() }}</div>
            @endif

        @elseif ($tab === 'applications' && $applications)
            @if ($applications->isEmpty())
                <p class="muted">No applications.</p>
            @else
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Applicant</th><th>Role</th><th>Status</th><th>When</th></tr></thead>
                        <tbody>
                            @foreach ($applications as $application)
                                <tr>
                                    <td>{{ $application->first_name }} {{ $application->last_name }}<br>
                                        <span class="muted">{{ $application->email }}</span></td>
                                    <td>{{ $application->opening?->title ?? '—' }}</td>
                                    <td><span class="pill">{{ $application->status }}</span></td>
                                    <td class="muted">{{ $application->created_at?->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pager">{{ $applications->links() }}</div>
            @endif
        @endif
    </div>
</section>

@endsection
