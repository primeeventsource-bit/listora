@php
    $faqs = $faqs ?? [
        [
            'Do you take a commission when my listing sells or rents?',
            'No. Listora is an advertising marketplace, not a broker. You pay one flat fee to publish a listing and that is the only money we ever take. When you agree terms with a traveler or buyer, the full amount is yours.',
        ],
        [
            'How do people contact me?',
            'Inquiries arrive in your Listora inbox and by email. Your address stays private until you reply. We never sell inquiries as leads, never pass your details to a third party, and never call you about your own listing.',
        ],
        [
            'What can I advertise?',
            'Vacation properties you own or control — a house, a villa, an apartment, a resort residence. Your listing captures the location, the layout, what is included, and when it is available, which is the information an interested visitor needs before they get in touch.',
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
