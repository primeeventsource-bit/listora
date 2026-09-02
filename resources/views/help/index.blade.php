@extends('layouts.app')

@section('title', 'Help — Listora')
@section('meta', 'Search the Listora help centre, ask our assistant a question, or write to a person. Answers about advertising plans, ownership verification, inquiries and offers.')

@php
    $brand = config('listora.brand');
    $categoryLabels = [
        'getting-started' => 'Getting started',
        'advertising'     => 'Advertising & plans',
        'verification'    => 'Ownership verification',
        'offers'          => 'Inquiries & offers',
        'account'         => 'Your account',
        'safety'          => 'Safety & payments',
        'general'         => 'General',
    ];
@endphp

@section('content')

<div class="page-head photo">
    <img class="bg" src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=2000&h=900&q=75" alt="" loading="eager">
    <div class="wrap">
        <span class="eyebrow">Help</span>
        <h1>Answers, and a person when you need one</h1>
        <p>
            Search the help centre, ask our assistant, or write to us directly. Every question that
            reaches us gets a reference you can quote back — nothing lands in a black hole.
        </p>
    </div>
</div>

{{-- ------------------------------------------------------------------
     Search. Hits /help/search, the same JSON endpoint the assistant's
     search tool uses, so both give the same answers.
------------------------------------------------------------------- --}}
<section class="help-search-band">
    <div class="wrap">
        <form class="help-search" role="search" onsubmit="return false">
            <label for="helpQuery" class="sr-only">Search help articles</label>
            <input
                type="search"
                id="helpQuery"
                placeholder="Search — try “ownership verification” or “how do offers work”"
                autocomplete="off"
                aria-describedby="helpResultsHint">
            <span class="help-search-hint" id="helpResultsHint">Results appear as you type</span>
        </form>
        <div id="helpResults" class="help-results" role="region" aria-live="polite"></div>
    </div>
</section>

<section>
    <div class="wrap">
        <div class="help-grid">

            {{-- ---------------------------------------------------------
                 AI assistant. Anonymous-friendly: no account needed to
                 ask. It quotes the help centre rather than improvising,
                 and hands off to a human when it cannot answer.
            ---------------------------------------------------------- --}}
            <div class="help-chat reveal" id="helpChat">
                <div class="help-chat-head">
                    <span class="eyebrow">Ask Listora</span>
                    <h2>Ask a question and get an answer now</h2>
                    <p class="muted">
                        Our assistant answers from the help centre. It will not guess at policy, and if your
                        question needs a person it will open a ticket and tell you so.
                    </p>
                </div>

                <div class="chat-log" id="chatLog" role="log" aria-live="polite" aria-label="Conversation">
                    <div class="chat-msg from-bot">
                        <p>
                            Hi — ask me anything about advertising a property, how ownership verification works,
                            or what happens after someone makes an offer.
                        </p>
                    </div>
                </div>

                <form class="chat-form" id="chatForm">
                    <label for="chatInput" class="sr-only">Your question</label>
                    <input
                        type="text"
                        id="chatInput"
                        placeholder="Type your question…"
                        maxlength="4000"
                        autocomplete="off"
                        required>
                    <button type="submit" class="btn btn-primary" id="chatSend">Send</button>
                </form>

                <p class="chat-foot muted">
                    Please don't share card or bank details here. Listora never takes payment on this site and
                    will never ask you for them.
                </p>
            </div>

            {{-- ---------------------------------------------------------
                 Contact details.
            ---------------------------------------------------------- --}}
            <aside class="help-contact reveal">
                <h3>Contact us</h3>

                <div class="contact-row">
                    <span class="contact-label">Email</span>
                    <a href="mailto:{{ $brand['email'] }}" class="contact-value">{{ $brand['email'] }}</a>
                    <span class="contact-note muted">We reply {{ $brand['response_time'] }}.</span>
                </div>

                @if (! empty($brand['phone']))
                    <div class="contact-row">
                        <span class="contact-label">Phone</span>
                        <span class="contact-value">{{ $brand['phone'] }}</span>
                        @if (! empty($brand['phone_is_placeholder']))
                            {{-- Shown plainly rather than dressed up as a live line. A number
                                 that rings nowhere is worse than no number at all. --}}
                            <span class="contact-note muted">
                                Our phone line is not open yet — email and the assistant above are the
                                fastest ways to reach us.
                            </span>
                        @endif
                    </div>
                @endif

                <div class="contact-row">
                    <span class="contact-label">{{ $brand['location']['label'] }}</span>
                    <span class="contact-value">{{ $brand['location']['country'] }}</span>
                    <span class="contact-note muted">
                        We're an online platform and don't have a walk-in office — everything is handled
                        here, by email, or by phone once the line opens.
                    </span>
                </div>

                <div class="contact-row">
                    <span class="contact-label">Hours</span>
                    <span class="contact-value">Monday to Friday</span>
                    <span class="contact-note muted">
                        The assistant above answers at any hour. A person picks up questions on business days.
                    </span>
                </div>

                <hr>

                <p class="muted" style="margin:0">
                    <strong>A note on money.</strong> Listora is an advertising platform. We are not a broker,
                    take no commission, and are never part of what you agree with the other party. If anyone
                    asks you to send funds to Listora, it is not us — tell us immediately.
                </p>
            </aside>
        </div>
    </div>
</section>

{{-- ------------------------------------------------------------------
     Ask a question — the written form, for anything the assistant
     could not settle. Persists with a reference.
------------------------------------------------------------------- --}}
<section class="band" id="ask">
    <div class="wrap">
        <div class="section-head reveal">
            <span class="eyebrow">Ask a question</span>
            <h2>Would you rather write to a person?</h2>
            <p class="lead">
                Send it here and it goes straight to our team with a reference number. No queue ticket,
                no bot loop — a person reads it and replies by email {{ $brand['response_time'] }}.
            </p>
        </div>

        @if (session('contact_success'))
            <div class="notice reveal">
                <p><strong>{{ session('contact_success') }}</strong></p>
                @if (session('contact_reference'))
                    <p>
                        Your reference is <strong>{{ session('contact_reference') }}</strong> — quote it if you
                        write to us again about the same thing.
                    </p>
                @endif
            </div>
        @endif

        @if ($errors->any())
            <div class="notice error reveal">
                <p><strong>Something needs fixing before we can send this:</strong></p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('contact.store') }}" class="ask-form reveal">
            @csrf

            <div class="frow">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" maxlength="80" required>
                </div>
                <div class="field">
                    <label for="last_name">Last name</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" maxlength="80" required>
                </div>
            </div>

            <div class="frow">
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="160" required>
                    <span class="field-hint">This is where the reply goes.</span>
                </div>
                <div class="field">
                    <label for="phone">Phone <span class="muted">(optional)</span></label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" maxlength="40">
                </div>
            </div>

            <div class="field">
                <label for="department">What is this about?</label>
                <select id="department" name="department" required>
                    @foreach ($departments as $value => $label)
                        <option value="{{ $value }}" @selected(old('department') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" value="{{ old('subject') }}" maxlength="200" required>
            </div>

            <div class="field">
                <label for="message">Your question</label>
                <textarea id="message" name="message" rows="6" maxlength="5000" required>{{ old('message') }}</textarea>
                <span class="field-hint">
                    If it's about a specific listing or offer, include the reference — it saves us a round trip.
                </span>
            </div>

            <button type="submit" class="btn btn-primary">Send question</button>
        </form>
    </div>
</section>

{{-- ------------------------------------------------------------------
     Browse the help centre. Empty state is honest rather than fake.
------------------------------------------------------------------- --}}
<section>
    <div class="wrap">
        <div class="section-head reveal">
            <span class="eyebrow">Help centre</span>
            <h2>Browse by topic</h2>
        </div>

        @if ($categories->isEmpty())
            <p class="muted reveal">
                We're still writing these up. In the meantime the assistant above and
                <a href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a> will both get you an answer.
            </p>
        @else
            <div class="help-topics">
                @foreach ($categories as $category => $articles)
                    <div class="help-topic reveal">
                        <h3>{{ $categoryLabels[$category] ?? Str::headline($category) }}</h3>
                        <ul>
                            @foreach ($articles as $article)
                                <li>
                                    <a href="{{ route('help.show', $article->slug) }}">{{ $article->title }}</a>
                                    <span class="muted">{{ $article->summary }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section class="band">
    <div class="wrap">
        <div class="section-head reveal">
            <span class="eyebrow">Common questions</span>
            <h2>The things people ask us most</h2>
        </div>
        @include('partials.faq')
    </div>
</section>

@endsection

@push('scripts')
<script>
(function () {
    const brandEmail = @json($brand['email']);

    // ---------------------------------------------------------------
    // Help search. Debounced so typing doesn't fire a request per key.
    // ---------------------------------------------------------------
    const query   = document.getElementById('helpQuery');
    const results = document.getElementById('helpResults');
    let searchTimer;

    if (query) {
        query.addEventListener('input', function () {
            clearTimeout(searchTimer);
            const term = query.value.trim();

            if (term.length < 2) {
                results.innerHTML = '';
                return;
            }

            searchTimer = setTimeout(async function () {
                try {
                    const res  = await fetch('{{ route('help.search') }}?q=' + encodeURIComponent(term), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await res.json();

                    if (!data.results || data.results.length === 0) {
                        results.innerHTML =
                            '<p class="muted">No articles matched. Try the assistant below, or email ' +
                            '<a href="mailto:' + brandEmail + '">' + brandEmail + '</a>.</p>';
                        return;
                    }

                    results.innerHTML = data.results.map(function (r) {
                        return '<a class="help-result" href="' + r.url + '">' +
                               '<strong></strong><span></span></a>';
                    }).join('');

                    // Titles and summaries are set as text, never interpolated
                    // into the HTML above — article copy is operator-authored
                    // and must not be able to inject markup into this page.
                    results.querySelectorAll('.help-result').forEach(function (el, i) {
                        el.querySelector('strong').textContent = data.results[i].title;
                        el.querySelector('span').textContent    = data.results[i].summary;
                    });
                } catch (e) {
                    results.innerHTML =
                        '<p class="muted">Search is unavailable right now — please try the form below.</p>';
                }
            }, 250);
        });
    }

    // ---------------------------------------------------------------
    // AI assistant.
    // ---------------------------------------------------------------
    const form  = document.getElementById('chatForm');
    const input = document.getElementById('chatInput');
    const log   = document.getElementById('chatLog');
    const send  = document.getElementById('chatSend');

    if (!form) return;

    // Stable per browser so a conversation survives a page reload, and so
    // the server can prove an anonymous session belongs to this visitor
    // before resuming it.
    let visitorId = localStorage.getItem('listora_visitor_id');
    if (!visitorId) {
        visitorId = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random());
        localStorage.setItem('listora_visitor_id', visitorId);
    }
    let sessionId = null;

    function addMessage(text, who) {
        const wrap = document.createElement('div');
        wrap.className = 'chat-msg from-' + who;
        const p = document.createElement('p');
        p.textContent = text;                 // textContent, never innerHTML
        wrap.appendChild(p);
        log.appendChild(wrap);
        log.scrollTop = log.scrollHeight;
        return wrap;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const message = input.value.trim();
        if (!message) return;

        addMessage(message, 'user');
        input.value = '';
        input.disabled = true;
        send.disabled  = true;

        const thinking = addMessage('…', 'bot');

        try {
            const res = await fetch('/api/v1/support/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Listora-Surface': 'web',
                },
                body: JSON.stringify({
                    message: message,
                    visitor_id: visitorId,
                    session_id: sessionId,
                }),
            });

            const data = await res.json();
            sessionId = data.session_id || sessionId;

            thinking.querySelector('p').textContent = data.reply ||
                'Sorry — something went wrong. Please email ' + brandEmail + '.';
        } catch (err) {
            thinking.querySelector('p').textContent =
                'I could not reach our assistant just now. Please use the question form below, or email ' +
                brandEmail + '.';
        } finally {
            input.disabled = false;
            send.disabled  = false;
            input.focus();
        }
    });
})();
</script>
@endpush
