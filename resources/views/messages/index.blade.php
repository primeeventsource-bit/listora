{{--
    The inbox, from whichever side you are on.

    Deliberately one screen for both parties. A thread is the same object to an
    owner and to a visitor, and the only thing that differs is whose name sits
    opposite yours - which the model answers with counterpartFor().
--}}
@extends('layouts.member')

@section('title', 'Messages')
@section('crumb', 'Messages')

@section('content')

<div class="c-head">
    <div>
        <h1 class="c-head__t">Messages</h1>
        <p class="c-head__s">
            Conversations about advertised properties. Listora carries them and stays out of them.
        </p>
    </div>
</div>

@if ($conversations->isEmpty())
    <div class="c-card">
        <div class="c-empty">
            <h3>No conversations yet</h3>
            <p>
                A conversation starts when someone sends an inquiry or an offer on a listing,
                and continues here. Nothing has been started with you yet.
            </p>
            <p style="margin-top:16px">
                <a href="{{ route('listings.index') }}" class="c-btn">Browse listings</a>
            </p>
        </div>
    </div>
@else
    <div class="c-card">
        <div class="c-card__b--flush">
            <table class="c-table">
                <thead>
                    <tr>
                        <th scope="col">With</th>
                        <th scope="col">About</th>
                        <th scope="col" class="c-table__num">Messages</th>
                        <th scope="col">Last message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($conversations as $conversation)
                        @php
                            $counterpart = $conversation->counterpartFor($me);
                            $unread = $conversation->isUnreadFor($me);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('messages.show', $conversation) }}">
                                    <strong>{{ $counterpart?->name ?? 'Deleted account' }}</strong>
                                </a>
                                @if ($unread)
                                    <span class="c-pill c-pill--pending" style="margin-left:6px">New</span>
                                @endif
                                <div class="c-table__muted">
                                    {{ $conversation->owner_user_id === $me->id ? 'Interested visitor' : 'Property owner' }}
                                </div>
                            </td>
                            <td>
                                {{ $conversation->listing?->title ?? 'Listing removed' }}
                                @if ($conversation->listing?->ad_number)
                                    <div class="c-table__muted">#{{ $conversation->listing->ad_number }}</div>
                                @endif
                            </td>
                            <td class="c-table__num">{{ number_format($conversation->messages_count) }}</td>
                            <td class="c-table__muted">
                                {{ $conversation->last_message_at?->diffForHumans() ?? 'Not started' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="c-card__b">{{ $conversations->links() }}</div>
    </div>
@endif

@endsection
