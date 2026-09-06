<?php

namespace Database\Seeders;

use App\Enums\HelpAudience;
use App\Models\HelpArticle;
use Illuminate\Database\Seeder;

/**
 * The Listora help centre.
 *
 * These articles are load-bearing, not filler. The support assistant is
 * instructed to call `search_help_articles` and quote ONLY what comes back for
 * any question about policy — so an empty help centre does not merely leave
 * the page bare, it leaves the assistant with nothing to answer from. Every
 * claim the site makes about fees, verification, and who holds the money needs
 * a home here.
 *
 * Seeded with updateOrCreate on `slug` so re-running edits copy in place
 * rather than duplicating articles.
 */
class HelpArticleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->articles() as $i => $article) {
            HelpArticle::updateOrCreate(
                ['slug' => $article['slug']],
                $article + ['sort_order' => ($i + 1) * 10, 'is_published' => true],
            );
        }
    }

    private function articles(): array
    {
        return [
            // ---------------------------------------------- getting started
            [
                'slug' => 'what-listora-is',
                'audience' => HelpAudience::All,
                'category' => 'getting-started',
                'title' => 'What Listora is — and what it is not',
                'summary' => 'Listora advertises vacation properties. It is not a broker, an agency, or a booking site.',
                'search_keywords' => 'what is listora, broker, agency, commission, advertising platform, advertise',
                'body' => <<<'TXT'
Listora is an advertising platform. Owners pay one flat fee to publish a listing for 180 days, and visitors and buyers browse those listings and contact the owner directly.

That is the whole model, and the things it rules out matter as much as the things it includes:

Listora does not take reservations or hold dates. There is no checkout and no confirmation number, because there is nothing for us to confirm.

Listora does not collect rental payments, hold funds in escrow, or pay owners out. Money never passes through us.

Listora does not take a commission. Whatever you agree with the other party is entirely yours.

Listora is not a party to your arrangement. When an owner accepts an inquiry or an offer, the two sides exchange details and settle dates, price, and terms between themselves.

What we do is make the listing credible and easy to find: we verify that the person advertising actually owns what they are advertising, we present the facts a buyer needs to compare options, and we put the two of you in touch.
TXT,
            ],
            [
                'slug' => 'browsing-without-an-account',
                'audience' => HelpAudience::Traveler,
                'category' => 'getting-started',
                'title' => 'Do I need an account to browse or make contact?',
                'summary' => 'No. Browsing, searching, and sending an inquiry or offer all work without signing up.',
                'search_keywords' => 'account, sign up, register, login, browse, anonymous, contact owner',
                'body' => <<<'TXT'
No account is needed to browse listings, search, or send an inquiry or an offer to an owner. Requiring one would put a wall in front of the only thing the site is for.

An account is worth having if you want to keep track of the offers you have made and see the owner's replies in one place rather than only in your email.

Owners do need an account, because a listing has to belong to someone we can verify and contact.
TXT,
            ],

            // ---------------------------------------------- advertising
            [
                'slug' => 'advertising-plans',
                'audience' => HelpAudience::Owner,
                'category' => 'advertising',
                'title' => 'The three advertising plans',
                'summary' => 'Starter, Explorer, and Signature. One flat fee, 180 days, no commission on what you agree.',
                'search_keywords' => 'plans, pricing, starter, explorer, signature, cost, fee, how much, commission',
                'body' => <<<'TXT'
Every plan buys 180 days of advertising, billed upfront. The fee is flat and it is the only money Listora ever takes — we do not take a percentage of what you agree with a traveler or buyer.

Starter, $995, covers one property: standard search visibility, a professional listing presentation, one Google Ads campaign, one Facebook and Instagram campaign set, basic SEO and metadata work, a monthly email blast, and a monthly performance report.

Explorer, $1,995, covers up to three properties and adds reach: enhanced search visibility, a featured listing with priority placement, two Google Ads campaigns with retargeting, social ads across Facebook, Instagram and TikTok, two email blasts a month with automation, advanced SEO and local optimization, audience targeting and retargeting, and a monthly performance report with recommendations.

Signature, $3,995, covers up to five properties and adds our team's work: premier search visibility, highest priority placement, three or more Google Ads campaigns with retargeting and display, full campaigns across Facebook, Instagram, TikTok and YouTube, weekly email blasts with automation, video marketing, reputation management, a real-time analytics dashboard, and a dedicated account manager.

Plans are arranged with us directly rather than paid for on the website. Ask on the Help page or email us and we will set it up.
TXT,
            ],
            [
                'slug' => 'what-happens-after-i-submit',
                'audience' => HelpAudience::Owner,
                'category' => 'advertising',
                'title' => 'What happens after I submit a listing',
                'summary' => 'Your submission becomes a draft, we verify ownership, then it publishes. You get a reference at every step.',
                'search_keywords' => 'submit, draft, review, publish, how long, waiting, status, reference',
                'body' => <<<'TXT'
When you complete the "Advertise" form, we create a draft and give you a reference beginning with LST-D. Keep it — quoting it to us is the fastest way to get an answer about your listing.

A draft is not visible to anyone browsing the site. It stays private until it has passed ownership verification, which is the promise every plan makes.

Our team then reviews what you sent against the documentation you provide. Most drafts clear in one to two business days. If something does not line up, we come back to you directly rather than declining quietly.

Once verification passes and your plan is arranged, we publish the listing and your 180-day term begins from that date — not from the date you submitted.
TXT,
            ],
            [
                'slug' => 'listing-did-not-sell',
                'audience' => HelpAudience::Owner,
                'category' => 'advertising',
                'title' => 'What if my listing does not move within the year?',
                'summary' => 'Renew at half price. On Signature, renewal is free.',
                'search_keywords' => 'renew, renewal, expired, did not sell, no result, 180 days, term, extend',
                'body' => <<<'TXT'
If your 180 days run out without a result, you can renew at half the original fee. On the Signature plan, renewal is free.

We would rather keep a good listing live than take another full fee from someone who has not had a result yet.

About thirty days before your term ends we will email you so nothing lapses by surprise. An expired listing stops appearing in browse but is not deleted — renewing brings it back with its photos, copy, and view count intact.
TXT,
            ],

            // ---------------------------------------------- verification
            [
                'slug' => 'ownership-verification',
                'audience' => HelpAudience::Owner,
                'category' => 'verification',
                'title' => 'What ownership verification involves',
                'summary' => 'We check your deed, title, or other proof of ownership against the details you entered, before the listing publishes.',
                'search_keywords' => 'verification, verify, ownership, deed, certificate, documents, proof, how long',
                'body' => <<<'TXT'
Before a listing publishes, our team reviews your documentation and checks that it matches what you entered.

For a vacation property that usually means a deed or title, or another document showing the property is yours to advertise. For a property you manage on an owner’s behalf, we ask for written authority from the owner. For a membership certificate or a statement showing the resort, week number, season, and usage year.

We are checking that the property matches, the location matches, and the availability matches. These are the facts a visitor compares options on, so a listing that overstates any of them is worse than no listing at all.

It usually takes one to two business days. If something does not line up we contact you directly and explain what we need — a decline always comes with a reason.

This is also why Listora listings carry a verified marker and why we are comfortable putting it there.
TXT,
            ],
            [
                'slug' => 'why-verification-matters-to-buyers',
                'audience' => HelpAudience::Traveler,
                'category' => 'verification',
                'title' => 'What "verified" means on a listing',
                'summary' => 'It means our team checked the owner\'s documentation against the listing details before it published.',
                'search_keywords' => 'verified badge, trust, scam, legitimate, real, safe, confirmed',
                'body' => <<<'TXT'
Every listing on Listora is checked before it publishes. Our team reviews the owner's ownership documents and confirms that the property, the location, and the availability all match what the listing claims.

That is a meaningful check, and it is worth being precise about what it does and does not cover.

It does cover: that the person advertising holds what they say they hold, and that the headline facts are accurate as of the review date.

It does not cover: the condition of the unit on the day you arrive, whether a particular date is available, or the terms you go on to agree. Listora is not a party to your arrangement and cannot guarantee those.

If something about a listing looks wrong to you, tell us. We would rather pull a listing than leave it up.
TXT,
            ],

            // ---------------------------------------------- offers
            [
                'slug' => 'how-inquiries-and-offers-work',
                'audience' => HelpAudience::All,
                'category' => 'offers',
                'title' => 'How inquiries and offers work',
                'summary' => 'An inquiry is a question. An offer names a price. Both expire, and both go straight to the owner.',
                'search_keywords' => 'offer, inquiry, enquiry, contact owner, expire, accept, decline, reply',
                'body' => <<<'TXT'
There are two ways to reach an owner, and the difference is simply whether you name a price.

An inquiry is a message with a question — about availability, the property, the location, anything you need to know. No price attached.

An offer names an amount. Everything else works the same way.

Both arrive in the owner's Listora inbox and by email, and both carry a reference beginning with LST-F that either side can quote to us.

Open offers expire after 72 hours by default. That is deliberate: it means an owner's silence resolves into something definite instead of leaving you waiting indefinitely.

If an owner accepts, we share contact details and the two of you take it from there — dates, price, and payment are agreed directly between you. Accepting does not reserve dates or move any money, because Listora is not part of that arrangement.
TXT,
            ],
            [
                'slug' => 'owner-never-replied',
                'audience' => HelpAudience::Traveler,
                'category' => 'offers',
                'title' => 'The owner has not replied — what now?',
                'summary' => 'Tell us. We hold the record of what you sent and can chase the owner, though we cannot answer for them.',
                'search_keywords' => 'no reply, not responding, ignored, silent, cannot reach owner, unresponsive',
                'body' => <<<'TXT'
Contact us and quote your offer reference. This is one of the requests we treat as high priority.

We hold the record of exactly what you sent and when, so we can confirm it arrived and follow up with the owner directly.

What we can do: chase the owner, confirm the listing is still active, and take a listing down if an owner has stopped responding altogether.

What we cannot do: answer on the owner's behalf, commit them to anything, or hold dates for you. We are not a party to the arrangement.

If your offer has already expired you are free to send another, or to contact the owner about a different listing.
TXT,
            ],

            // ---------------------------------------------- safety
            [
                'slug' => 'how-money-should-change-hands',
                'audience' => HelpAudience::All,
                'category' => 'safety',
                'title' => 'How money should change hands',
                'summary' => 'Directly between the two of you — never through Listora. We never ask you to send us funds.',
                'search_keywords' => 'payment, pay, deposit, escrow, wire, scam, fraud, money, refund, card',
                'body' => <<<'TXT'
Listora does not process payments. We hold no card details, no bank details, and no merchant account. There is no field anywhere on this site that takes a payment.

That makes one rule very simple: if anyone asks you to send money to Listora, it is not us. Tell us immediately.

For rentals, we suggest a payment method that carries buyer protection, and agreeing the full terms in writing before any money moves.

For a transfer of ownership, use a licensed escrow or closing company. This is a real transfer of title and it is worth doing properly.

Never send a deposit by wire transfer or gift card to someone you have not verified, on this site or anywhere else.

The only money Listora ever collects is the flat advertising fee an owner pays us, and that is arranged with us directly.
TXT,
            ],
            [
                'slug' => 'reporting-a-problem-listing',
                'audience' => HelpAudience::All,
                'category' => 'safety',
                'title' => 'Reporting a listing or a user',
                'summary' => 'Send us the listing reference and what you saw. We would rather pull a listing than leave a bad one up.',
                'search_keywords' => 'report, scam, fake, fraud, suspicious, wrong, complaint, take down',
                'body' => <<<'TXT'
If a listing looks inaccurate, or someone using the site behaves in a way that concerns you, contact us with the listing reference and a description of what you saw.

We take these seriously. Every listing carries a verification record naming who reviewed it and when, so we can go back and check exactly what was confirmed.

If a listing turns out to be inaccurate we correct it or take it down. Our reputation rests on the verified marker meaning something.

If you have lost money to someone you met through Listora, tell us and also report it to your local authorities and your bank or card issuer. We will provide whatever record we hold to support your case.
TXT,
            ],

            // ---------------------------------------------- account
            [
                'slug' => 'managing-your-listing',
                'audience' => HelpAudience::Owner,
                'category' => 'account',
                'title' => 'Editing, pausing, and resuming your listing',
                'summary' => 'Edit copy, photos, and your asking price any time. Pause it if you need to stop inquiries for a while.',
                'search_keywords' => 'edit, update, change, pause, resume, hide, photos, price, manage listing',
                'body' => <<<'TXT'
Your listings live under your account. You can edit the title, description, photos, and asking price at any time, and changes go live immediately — those are your details to change.

Pausing takes the listing out of browse and stops new inquiries without ending your term. Useful if you have a deal in progress and do not want more messages, or if you need to step away for a while.

Resuming puts it straight back. Your term keeps running while a listing is paused, so a long pause spends time you have paid for.

Some things you cannot change yourself: the verification status, the plan, and the term dates. Those record what our team confirmed and what was commercially agreed, so contact us and we will handle them.
TXT,
            ],
            [
                'slug' => 'privacy-of-your-details',
                'audience' => HelpAudience::Owner,
                'category' => 'account',
                'title' => 'Who can see your contact details',
                'summary' => 'Your email and phone stay private until you reply to someone. We never sell inquiries as leads.',
                'search_keywords' => 'privacy, private, email, phone, spam, leads, data, share details',
                'body' => <<<'TXT'
Your email address and phone number are not shown on your listing. Someone interested sends an inquiry or an offer through Listora, and your details stay private until you choose to reply.

We do not sell inquiries as leads, we do not pass your details to third parties, and we will never call you about your own listing to sell you something.

When you accept an offer, contact details are exchanged with that person specifically, because at that point the two of you need to be able to talk directly.

You can ask us to remove your account and listings at any time.
TXT,
            ],
        ];
    }
}
