@extends('layouts.legal')

@section('title', 'Privacy Policy — Listora')

@section('content')
<div class="wrap-sm legal-doc">

    <h1>Privacy Policy</h1>
    {{-- Printed from the registry, not typed here: a version bumped in
         one place and not the other means a visitor accepts one version
         while reading another. --}}
    <p class="legal-meta">Version {{ $versionLabel }} · Effective 16 August 2026</p>

    <h2>1. Who we are</h2>
    <p>
        Listora operates an advertising platform for vacation properties. Our headquarters are in
        the United States. For any privacy question,
        write to help&#64;listora1.com.
    </p>

    <h2>2. What we collect</h2>
    <p><strong>Information you give us.</strong> Your name, email address, phone number, and
    password when you create an account. Listing details and photographs when you advertise.
    The contents of inquiries, offers, and messages you send through the site.</p>

    <p><strong>Ownership documentation.</strong> Deeds, titles, and other documents you
    provide to show a property is yours to advertise.</p>

    <p><strong>Information collected automatically.</strong> IP address, browser and device type,
    operating system, the pages you view, the page or advertisement that referred you, any campaign
    tags in the link you followed, a session identifier, a visitor identifier, and the approximate
    location derived from your IP address.</p>

    <p><strong>Activity records.</strong> We keep a timestamped record of activity on this site,
    for visitors who are signed in and for visitors who are not. Alongside the information above,
    that record includes the pages and listings you view, the advertisements you view, inquiries
    and offers you start and submit, messages you send, account creation, sign-in and sign-out,
    listings you create or edit, acceptance of our Terms and agreements, and — when we begin
    taking payment for advertising — activity on payment pages. We keep it to operate and secure
    the site, to prevent and investigate fraud, to measure advertising, and to be able to evidence
    what happened if a transaction or a listing is disputed.</p>

    <p><strong>Visitors who are not signed in.</strong> A visitor who has not signed in is
    recorded against a visitor identifier rather than a name. If you later create an account or
    sign in, activity recorded under that identifier can be associated with your account. That
    association is available to our own administrators and is never shown to an advertiser.</p>

    <p><strong>About approximate location.</strong> Location is estimated from your IP address
    against a geolocation database. It identifies a city and region near the network you are
    connecting through, which is often not the place you are actually in and is never a street
    address, a precise position, or a live location. We store and display it only as approximate.</p>

    <p><strong>What we do not collect.</strong> We do not collect or store card numbers, bank
    account details, or any payment credentials, because no payment is processed through this
    website.</p>

    <h2>3. Why we use it</h2>
    <ul>
        <li>To operate your account and publish your listings.</li>
        <li>To verify ownership before a listing is published.</li>
        <li>To pass inquiries and offers to the relevant listing owner.</li>
        <li>To detect and investigate fraud, abuse, and unauthorised account access.</li>
        <li>To respond to support requests.</li>
        <li>
            To measure how advertisements on this site perform — how many people viewed a
            listing, roughly where they were, what kind of device they used, and which campaign
            or referring site brought them — and to show that performance to the advertiser
            whose listing it was.
        </li>
        <li>
            To evidence to an advertiser that the advertising they paid for was published and
            reachable by the public for the period agreed.
        </li>
        <li>To send service messages about your listings, including term expiry notices.</li>
    </ul>

    <h2>4. Our lawful bases</h2>
    <p>
        We process personal data to perform our contract with you, to pursue our legitimate
        interests in operating a trustworthy marketplace and preventing fraud, to comply with
        legal obligations, and — for optional marketing email — with your consent.
    </p>

    <h2>5. What we share, and what we never do</h2>
    <p>
        When you send an inquiry or an offer, the listing owner receives your message and the
        contact details you supplied. When an owner accepts an offer, contact details are
        exchanged between the two of you.
    </p>
    <p>
        <strong>Advertisers see traffic to their own listings.</strong> If you visit a listing,
        the advertiser of that listing can see that the visit happened and, for that visit, the
        approximate city and region, the device type and browser, and the campaign or referring
        site it came from. They see this only for their own advertisements.
    </p>
    <p>
        <strong>Advertisers never see your IP address.</strong> It is recorded, but it is
        restricted to our own administrators for security and fraud investigation, and it is not
        available on any screen an advertiser can reach. Advertisers are not told who you are:
        they see a visit, not a visitor.
    </p>
    <p>
        <strong>The full activity record is administrator-only.</strong> The complete history
        described in section 2 — including IP addresses, the sequence of pages in a visit, and
        any association between a visitor identifier and an account — can be searched, viewed
        and exported only by our own authorized administrators, under access controls that are
        granted individually. It is not shared with advertisers, and we do not sell it or make
        it available to third parties for their own purposes.
    </p>
    <p>
        If you are signed in when you view a listing, we associate that visit with your account
        so that we can investigate abuse and answer your own data requests accurately. That
        association is visible to our administrators and is not shown to the advertiser.
    </p>
    <p>
        We share data with service providers who host our infrastructure, send our email, and
        provide geolocation, mapping, and AI support-assistant functionality, under contract and
        only as needed to provide those services. We disclose data where required by law.
    </p>
    <p>
        <strong>We do not sell personal data, and we do not sell inquiries as sales leads.</strong>
        We do not pass your contact details to third-party marketers.
    </p>

    <h2>6. The AI support assistant</h2>
    <p>
        The Help page assistant sends the text of your conversation to our AI provider to
        generate a reply. Do not enter payment credentials or sensitive personal information into
        it. Conversations are retained so we can follow up on support requests.
    </p>

    <h2>7. Owner contact details</h2>
    <p>
        A listing owner's email address and phone number are not displayed publicly on a listing.
        They are shared with a specific person only when the owner replies or accepts an offer.
    </p>

    <h2>8. Retention</h2>
    <p>
        We keep account data for as long as your account is open. Listings, inquiries, and offers
        are retained after they close as a record of what was agreed and to resolve later
        disputes. Authentication and security records are retained for security investigation.
        We delete or anonymise data when it is no longer needed for these purposes.
    </p>
    <p>
        <strong>Advertising traffic records</strong> — the visits described in section 2 — are
        kept for <strong>24 months</strong> and then deleted, including the IP address recorded
        with them. They exist to report performance to an advertiser, to show that advertising
        ran during its term, and to investigate abuse, and 24 months covers a full advertising
        term with room to answer a question about it afterwards.
    </p>

    <h2>9. Your rights</h2>
    <p>
        Depending on where you live, you may have the right to access, correct, delete, port, or
        restrict the processing of your personal data, and to object to processing based on
        legitimate interests. You may withdraw marketing consent at any time.
    </p>
    <p>
        To exercise any of these, write to help&#64;listora1.com. We may need to verify your
        identity first.
    </p>

    <h2>10. Cookies</h2>
    <p>
        We use cookies necessary to keep you signed in and to secure form submissions, and a
        local identifier so an anonymous support-chat conversation survives a page reload.
    </p>

    <h2>11. International transfers</h2>
    <p>
        We are based in the United States and your data may be processed there. Where we transfer
        data from other regions we rely on appropriate safeguards.
    </p>

    <h2>12. Children</h2>
    <p>
        The service is not directed at children under 16 and we do not knowingly collect their
        personal data.
    </p>

    <h2>13. Changes</h2>
    <p>
        We may update this policy. Each version is recorded with its own identifier and content
        hash, and we will tell you about material changes.
    </p>

    <h2>14. Contact</h2>
    <p>
        Privacy questions and data-subject requests: help&#64;listora1.com.
    </p>

</div>
@endsection
