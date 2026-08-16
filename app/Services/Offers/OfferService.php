<?php

namespace App\Services\Offers;

use App\Enums\OfferKind;
use App\Enums\OfferStatus;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creating and resolving offers on a listing.
 *
 * The one rule worth stating out loud: accepting an offer exchanges contact
 * details and closes the record. It does not reserve dates, take a deposit, or
 * move money, because Listora is not a party to whatever the two sides agree.
 * Anything in here that looked like a reservation would be a lie told in the
 * product's own voice.
 */
class OfferService
{
    /**
     * Record a buyer's inquiry or priced offer against a listing.
     *
     * `owner_user_id` is denormalised at write time so the owner's queue reads
     * from one table, but every authorisation check still joins through
     * `listings.owner_id` — see Offer::scopeForListingsOwnedBy.
     */
    public function submit(Listing $listing, array $data, ?User $buyer, ?Request $request = null): Offer
    {
        $kind = ($data['offer_amount_cents'] ?? null) !== null
            ? OfferKind::Offer
            : OfferKind::Inquiry;

        return Offer::create([
            'listing_id' => $listing->id,
            'buyer_user_id' => $buyer?->id,
            'owner_user_id' => $listing->owner_id,
            'kind' => $kind,
            'status' => OfferStatus::Active,

            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'],
            'offer_amount_cents' => $data['offer_amount_cents'] ?? null,

            'arrive' => $data['arrive'] ?? null,
            'depart' => $data['depart'] ?? null,
            'guests' => $data['guests'] ?? null,

            'ip_address' => $request?->ip(),
            'expires_at' => now()->addHours((int) setting('offers.expiry_hours', Offer::EXPIRY_HOURS)),
        ]);
    }

    public function accept(Offer $offer, User $actor): Offer
    {
        return $this->resolve($offer, OfferStatus::Accepted, $actor);
    }

    public function decline(Offer $offer, User $actor): Offer
    {
        return $this->resolve($offer, OfferStatus::Declined, $actor);
    }

    /**
     * Move an open offer to a terminal state.
     *
     * Locked and re-checked inside the transaction: the owner dashboard and
     * the admin register can both reach this, and an offer that expired
     * between page render and click must not still be actionable.
     *
     * @throws RuntimeException when the offer is no longer open
     */
    private function resolve(Offer $offer, OfferStatus $status, User $actor): Offer
    {
        return DB::transaction(function () use ($offer, $status, $actor) {
            $fresh = Offer::query()->lockForUpdate()->findOrFail($offer->getKey());

            if (! $fresh->isActionable()) {
                throw new RuntimeException(
                    "Offer {$fresh->reference} is no longer open ({$fresh->status->value})."
                );
            }

            $fresh->forceFill([
                'status' => $status,
                'responded_at' => now(),
            ])->save();

            return $fresh;
        });
    }

    /**
     * Close out offers whose clock has run out.
     *
     * Returns the number swept. Run from the ExpireOffers command so an
     * owner's silence resolves to something definite rather than leaving a
     * buyer waiting indefinitely.
     */
    public function expireLapsed(): int
    {
        return Offer::query()->lapsed()->update([
            'status' => OfferStatus::Expired,
            'updated_at' => now(),
        ]);
    }
}
