# Listora — site content

Copy for every page and tab on **listora1.com**, in nav order. This is the
editable source of record: change it here, then change it in the template
named under each heading.

Two facts appear on nearly every page and are set in **one** place,
`listora-app/config/listora.php` under `brand` — never hard-code them into a
template:

| Item | Value | Notes |
|---|---|---|
| Contact email | **help@listora1.com** | Footer, Help page, article footers |
| Headquarters | **United States** | Country only — no walk-in office |
| Phone | (800) 555-0142 | ⚠️ **Placeholder.** A reserved fictional 555 number. The Help page says the line is not open yet. Replace it, or set `phone` to `null` and the row disappears. |
| Reply time | within one business day | Set expectations before someone writes, not after |

---

## The one rule the copy has to hold

Listora **advertises**. It is not a broker, takes no commission, holds no
money, and is never a party to what an owner and a traveler agree. It also
**takes no payment on the website at all** — plans are arranged directly.

Anything that implies otherwise — a "Book now", a "checkout", a "your booking
is confirmed" — is a factual error, not a style choice. It tells someone their
money or their dates are held by us when they are not.

---

## Nav

`Explore · Advertise · How It Works · Pricing · About · Help`

**Help replaced Contact.** The old tab was a bare `mailto:` link: it opened a
blank email client, gave no answers on the way, and left no record that anyone
had asked. `/contact` now 301-redirects to `/help#ask` so old links and
bookmarks still land somewhere useful.

---

## 1. Home — `/`
`resources/views/pages/home.blade.php`

**Headline:** List More. Reach More. Explore More.

**Purpose:** say what this is and what it costs within one screen.

**Sections:**
- *Advertise exactly what you hold* — the three listing kinds: vacation
  properties, resort club points, vacation weeks
- *Hand-picked, owner published* — every listing is verified before it appears
- *Simple on both sides* — how a listing becomes a conversation
- *Most platforms earn when you make a deal. We don't.* — the flat-fee model,
  stated as a difference rather than a boast
- *Built to stay out of the way*
- *New this month* — recently published listings
- *Manage anywhere* — mobile apps
- *One fee. Twelve months. No cut of your deal.* — closing CTA

**Primary CTAs:** Explore listings · Advertise yours

---

## 2. Explore — `/browse`
`resources/views/pages/browse.blade.php`

**Headline:** Browse every listing

**Purpose:** find something. Filters do the work, copy stays out of the way.

**Filters:** keyword · kind (property / club points / week) · rent or own ·
region · bedrooms · max price · sort

**Empty state:** say plainly that nothing matched and offer to widen the
filters — never show zero results with no way forward.

---

## 3. Listing detail — `/listing/{slug}`
`resources/views/pages/listing.blade.php`

**Sections:** From the owner · What's included · Ongoing costs · Similar
listings

**Contact box:** the traveler sends an **inquiry** (a question) or an
**offer** (names a price). Both get a reference beginning `LST-F` and expire
after 72 hours.

**Required line, near the contact box:**
> Replies come straight to your inbox — Listora never sits in the middle of the
> conversation.

**Never** show: a booking calendar, a "reserve" button, a total with fees, or
anything implying dates can be held here.

---

## 4. Advertise — `/list-your-property`
`resources/views/pages/list.blade.php`

**Headline:** Ten minutes now, twelve months of visibility

**Steps:** What are you advertising? · Renting it out, or passing it on? · The
details · Choose your plan · Where should we reach you?

**On submit:** the visitor gets a draft reference beginning `LST-D` and is told
what happens next — ownership review in one to two business days, and we come
back directly if anything doesn't line up.

**No account required.** An owner should not have to sign up to ask about
advertising.

**No payment step.** The plan is arranged with us directly. The wizard ends at
"we have your details".

---

## 5. How It Works — `/how-it-works`
`resources/views/pages/how.blade.php`

**Headline:** Publish it. Own the conversation.

**Sections:**
- *Listing fees. That's the whole business model.*
- *Before you list* — what to have ready (deed, club statement, or membership
  certificate)
- `#verification` anchor — linked from the footer and from Help

---

## 6. Pricing — `/pricing`
`resources/views/pages/pricing.blade.php`

**Headline:** One fee. Twelve months. No cut of your deal.

| Plan | Price | For |
|---|---|---|
| **Essential** | $89 | One property, points package, or week |
| **Featured** ★ most popular | $179 | Priority placement and visibility tools |
| **Premier** | $349 | Top-of-results placement, plus copy and photos by our team |

Every plan: live for 12 full months · ownership verified before publishing ·
direct messaging · appears in all search results · edit any time.

**Sections:** The parts we don't charge extra for · What owners ask about the
fee · closing CTA

**Renewal:** half price if a listing doesn't move within the year; free on
Premier.

> Prices shown here are display copy in `config/listora.php`. Nothing on the
> site charges them — no card is taken anywhere.

---

## 7. About — `/about`
`resources/views/pages/about.blade.php`

**Headline:** We built the place we wished existed

**Sections:**
- *Owners were being treated as leads, not as people with something good to
  offer* — why we started
- *Six things we will not do* (`#promise`)
- *What we check, and what stays your job* (`#safety`)
- *We answer our own email*
- `#terms` anchor — Terms & Privacy

---

## 8. Help — `/help` ← **new, replaces Contact**
`resources/views/help/index.blade.php`

**Headline:** Answers, and a person when you need one

**Intro:**
> Search the help centre, ask our assistant, or write to us directly. Every
> question that reaches us gets a reference you can quote back — nothing lands
> in a black hole.

### Sections, in order

**a. Search** — live search over the help centre. Hits `/help/search`, the same
JSON endpoint the AI assistant's search tool calls, so the page and the
assistant can never drift apart on what they answer.

**b. Ask Listora — the AI chat box**
> Our assistant answers from the help centre. It will not guess at policy, and
> if your question needs a person it will open a ticket and tell you so.

- Anonymous-friendly — no account needed
- Posts to `/api/v1/support/chat`; conversation survives a reload
- Standing footnote under the box:
  > Please don't share card or bank details here. Listora never takes payment
  > on this site and will never ask you for them.

**c. Contact us** (sidebar card)

| | |
|---|---|
| **Email** | help@listora1.com — *We reply within one business day.* |
| **Phone** | (800) 555-0142 — *Our phone line is not open yet — email and the assistant above are the fastest ways to reach us.* |
| **Headquarters** | United States — *We're an online marketplace and don't have a walk-in office.* |
| **Hours** | Monday to Friday — *The assistant answers at any hour. A person picks up on business days.* |

Closing note on the card:
> **A note on money.** Listora is an advertising platform. We are not a broker,
> take no commission, and are never part of what you agree with the other
> party. If anyone asks you to send funds to Listora, it is not us — tell us
> immediately.

**d. Ask a question** (`#ask`) — the written form, for anything the assistant
couldn't settle.
> Send it here and it goes straight to our team with a reference number. No
> queue ticket, no bot loop — a person reads it and replies by email within one
> business day.

Fields: first name · last name · email · phone *(optional)* · what this is
about · subject · your question. Submits to `POST /contact`, persists to
`contact_messages`, returns a quotable reference.

**e. Browse by topic** — the help centre, grouped.

**f. Common questions** — the shared FAQ partial.

---

## 9. Help article — `/help/{slug}`
`resources/views/help/show.blade.php`

Article body, then a footer offering the assistant, the question form, and the
email address. A dead end at the bottom of a help article is a support ticket
waiting to happen.

### Seeded articles — `database/seeders/HelpArticleSeeder.php`

These are load-bearing: the assistant is instructed to quote **only** what
search returns, so an empty help centre leaves it with nothing to answer from.

| Topic | Article |
|---|---|
| Getting started | What Listora is — and what it is not |
| Getting started | Do I need an account to browse or make contact? |
| Advertising | The three advertising plans |
| Advertising | What happens after I submit a listing |
| Advertising | What if my listing does not move within the year? |
| Verification | What ownership verification involves |
| Verification | What "verified" means on a listing |
| Offers | How inquiries and offers work |
| Offers | The owner has not replied — what now? |
| Safety | How money should change hands |
| Safety | Reporting a listing or a user |
| Account | Editing, pausing, and resuming your listing |
| Account | Who can see your contact details |

---

## 10. Apps — `/apps`
`resources/views/pages/apps.blade.php`

**Headline:** The whole marketplace, in your pocket

**Section:** Designed for the two things people actually do — browse, and
answer inquiries.

---

## Footer

**Explore:** Vacation Properties · Resort Club Points · Vacation Weeks ·
Available to Rent · Available to Own

**Advertise:** Create a Listing · Pricing · How It Works · Ownership
Verification · Mobile Apps

**Company:** About Listora · Our Promise · **Help & Contact** · Safe & Secure ·
Terms & Privacy

**Contact strip:** Headquarters — United States · help@listora1.com · Help
centre

**Legal line, on every page:**
> Listora is an advertising platform. We are not a broker and take no
> commission on any transaction between users.

---

## Voice

Plain, specific, and never salesy about money. The distinctive thing about
Listora is what it *doesn't* do, so the copy states limits as confidently as
features — "we stay out of it" is the product, not an apology.

Say the awkward thing directly: the phone line isn't open, the article didn't
answer it, the listing didn't sell. Every one of those has a next step
attached.

**Banned:** the word *timeshare*. Use vacation property, vacation club,
points-based ownership, or week.
