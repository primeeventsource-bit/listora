@extends('layouts.legal')

@section('title', 'Advertising Agreement — Listora')

@section('content')
<div class="wrap-sm legal-doc">

    <h1>Advertising Agreement</h1>
    {{-- Printed from the registry, not typed here: a version bumped in
         one place and not the other means a visitor accepts one version
         while reading another. --}}
    <p class="legal-meta">Version {{ $versionLabel }} · Effective 16 August 2026</p>

    <p class="legal-lead">
        This agreement applies to owners who advertise a vacation property on Listora.
        It sits alongside the Terms of Service.
    </p>

    <h2>1. What you are buying</h2>
    <p>
        An advertising plan buys the publication of one listing for a fixed term, beginning on
        the date the listing is published. You are buying advertising and nothing else. Listora
        does not undertake to find you a buyer or a renter, and does not guarantee any inquiry,
        offer, sale, or rental.
    </p>

    <h2>2. No commission, ever</h2>
    <p>
        Listora takes no commission, success fee, or percentage of anything you agree with a
        traveler or buyer. The flat plan fee is the only amount you ever owe us in connection
        with your listing.
    </p>

    <h2>3. How plans are arranged</h2>
    <p>
        Plans are arranged with Listora directly. No payment is taken through this website, and
        we will never ask you to send funds to Listora through a listing, an inquiry, or an
        offer.
    </p>

    <h2>4. Your obligations</h2>
    <ul>
        <li>You hold, or are authorised to act for the holder of, what you advertise.</li>
        <li>Every material fact in your listing is accurate — the property, its location, what is
            included, the dates it is available, and any costs a visitor would need to know.</li>
        <li>You hold the rights to every photograph you upload.</li>
        <li>You keep the listing accurate, and update or pause it once it is no longer available.</li>
        <li>You respond to inquiries and offers within a reasonable time.</li>
        <li>Your advertisement complies with the law that applies to you, including any local
            registration, licensing, or tax requirement for short-term rental.</li>
    </ul>

    <h2>5. Ownership verification</h2>
    <p>
        You agree to supply documentation evidencing what you advertise, and you authorise us to
        review it for the purpose of verification. Publication is conditional on verification.
        We may decline or remove a listing that fails it, and we will tell you why.
    </p>

    <h2>6. You deal with the other party directly</h2>
    <p>
        Listora introduces interested people to you and stops there. Dates, price, deposits,
        contracts, payment, and completion are matters between you and the other party. Listora
        is not a party to that arrangement, holds no funds, and provides no escrow or title
        service.
    </p>
    <p>
        For a transfer of ownership we recommend using a licensed escrow or closing company.
    </p>

    <h2>7. Term, renewal, and expiry</h2>
    <p>
        At the end of the term the listing stops appearing publicly. It is not deleted, and may
        be renewed. Where a listing has not produced a completed transaction within its term,
        renewal is offered at a reduced rate as described on the Pricing page.
    </p>

    <h2>8. Suspension and removal</h2>
    <p>
        We may pause or remove a listing that is inaccurate, breaches this agreement or the Terms
        of Service, or is the subject of a credible complaint. Where the fault is not yours we
        will discuss remedy or renewal with you.
    </p>

    <h2>9. Your content</h2>
    <p>
        You grant Listora a non-exclusive, royalty-free licence to host, display, reproduce, and
        distribute your listing content for the purpose of operating and promoting the
        marketplace and your listing, including in search results and marketing email.
    </p>

    <h2>10. Limitation of liability</h2>
    <p>
        Listora's aggregate liability under this agreement is limited to the advertising fees you
        paid for the listing concerned. We are not liable for lost profit, lost opportunity, or
        any dispute between you and a traveler or buyer.
    </p>

    <h2>11. Contact</h2>
    <p>
        Questions about this agreement: help&#64;listora1.com, or through the Help centre.
    </p>

</div>
@endsection
