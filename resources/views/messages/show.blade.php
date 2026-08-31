{{--
    One conversation.

    Read as a transcript, oldest first, so the thread makes sense top to
    bottom. The composer sits at the bottom for the same reason - it is the
    next thing after the last message, not a separate action.

    Nothing here shows an email address or a phone number. If the two parties
    want to move the conversation elsewhere they can say so in it; Listora does
    not hand out contact details on their behalf.
--}}
@extends('layouts.member')

@section('title', 'Conversation with '.($counterpart?->name ?? 'a member'))
@section('crumb', 'Messages')

@section('content')

<div class="c-head">
    <div>
        <h1 class="c-head__t">{{ $counterpart?->name ?? 'Deleted account' }}</h1>
        <p class="c-head__s">
            {{ $conversation->owner_user_id === $me->id ? 'Interested visitor' : 'Property owner' }}
            @if ($conversation->listing)
                &middot;
                <a href="{{ $conversation->listing->publicUrl() }}" target="_blank" rel="noopener"
                   style="color:var(--c-teal-dark)">{{ $conversation->listing->title }}</a>
                @if ($conversation->listing->ad_number)
                    <span class="c-table__muted">#{{ $conversation->listing->ad_number }}</span>
                @endif
            @endif
        </p>
    </div>
    <div class="c-head__actions">
        <a href="{{ route('messages.index') }}" class="c-btn c-btn--sm">All messages</a>
    </div>
</div>

@if (session('sent'))
    <div class="c-note">{{ session('sent') }}</div>
@endif

<div class="c-card" style="margin-bottom:16px">
    <div class="c-card__h"><h2 class="c-card__t">Conversation</h2></div>

    @if ($conversation->messages->isEmpty())
        <div class="c-empty">
            <h3>Nothing said yet</h3>
            <p>This conversation exists but has no messages. Write the first one below.</p>
        </div>
    @else
        <ul class="c-feed">
            @foreach ($conversation->messages as $message)
                @php $mine = $message->sender_user_id === $me->id; @endphp
                <li>
                    <span class="c-feed__dot {{ $mine ? '' : 'c-feed__dot--grey' }}"></span>
                    <span class="c-feed__txt">
                        <span class="c-feed__who">{{ $mine ? 'You' : ($message->sender?->name ?? 'Deleted account') }}</span>
                        {{-- Line breaks preserved: people write messages in
                             paragraphs and collapsing them makes a long one
                             unreadable. Escaped first, so the body cannot
                             carry markup. --}}
                        <div style="margin-top:3px;white-space:pre-wrap">{{ $message->body }}</div>
                    </span>
                    <span class="c-feed__when">{{ $message->created_at?->diffForHumans(short: true) }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>

<div class="c-card">
    <div class="c-card__h"><h2 class="c-card__t">Reply</h2></div>
    <div class="c-card__b">
        <form method="POST" action="{{ route('messages.store', $conversation) }}">
            @csrf
            <div class="c-field">
                <label for="body" class="sr-only">Your message</label>
                <textarea name="body" id="body" rows="4" required minlength="2" maxlength="4000"
                          placeholder="Write your message…">{{ old('body') }}</textarea>
                @error('body')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:10px">
                <p style="margin:0;font-size:12.5px;color:var(--c-ink-3)">
                    Arrangements you reach are between the two of you. Listora is not a party to
                    them and does not hold funds.
                </p>
                <button type="submit" class="c-btn c-btn--primary">Send</button>
            </div>
        </form>
    </div>
</div>

@endsection
