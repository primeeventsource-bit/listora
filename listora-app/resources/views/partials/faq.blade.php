@php
    $faqs = $faqs ?? [
        [
            'Do you take a commission when my listing sells or rents?',
            'No. Listora is an advertising marketplace, not a broker. You pay one flat fee to publish a listing and that is the only money we ever take. When you agree terms with a traveler or buyer, the full amount is yours.',
        ],
        [
            'What does ownership verification involve?',
            'Before a listing publishes, our team reviews your deed, club statement, or membership certificate and checks that it matches the details you entered — the resort, the week or points balance, the season, and the usage year. It usually takes one to two business days, and we come back to you directly if anything does not line up.',
        ],
        [
            'How do people contact me?',
            'Inquiries arrive in your Listora inbox and by email. Your address stays private until you reply. We never sell inquiries as leads, never pass your details to a third party, and never call you about your own listing.',
        ],
        [
            'Can I advertise club points as well as a property?',
            'Yes, and they are separate listing types with their own fields. A club points listing captures your balance, season, usage year, and what that balance realistically books at the resorts in your collection — which is the information buyers actually need to compare offers.',
        ],
        [
            'What if my listing does not move within the year?',
            'Renew at half price. On the Premier plan, renewal is free. We would rather keep a listing live than take another full fee from someone who has not had a result yet.',
        ],
        [
            'How should money change hands?',
            'That part is between you and the other party, and we deliberately stay out of it. For rentals we publish guidance on payment methods with buyer protection. For ownership transfers we recommend a licensed escrow or closing company, and we will never ask you to send funds to Listora.',
        ],
    ];
@endphp

<div class="faq reveal">
    @foreach ($faqs as $i => [$q, $a])
        <details @if ($i === 0) open @endif>
            <summary>{{ $q }}</summary>
            <div class="a"><p>{{ $a }}</p></div>
        </details>
    @endforeach
</div>
