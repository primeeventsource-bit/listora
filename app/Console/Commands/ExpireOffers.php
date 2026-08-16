<?php

namespace App\Console\Commands;

use App\Services\Offers\OfferService;
use Illuminate\Console\Command;

/**
 * Closes offers whose clock has run out.
 *
 * Scheduled every minute in routes/console.php. Without it, `expires_at`
 * passes and the row stays `active` forever: the buyer sees an open offer that
 * will never be answered, and every "open offers" count — the owner dashboard,
 * the operations overview, the admin register — is inflated by offers that are
 * not open at all.
 *
 * Offer::isActionable() checks hasLapsed() separately, so a lapsed offer can
 * never be accepted even between sweeps. That is a guard on the write path,
 * not a substitute for this: it stops a bad action, it does not correct the
 * numbers anyone is reading.
 *
 * Expiring is the whole point of the 72-hour clock. It is what turns an
 * owner's silence into a definite answer instead of leaving a buyer waiting.
 */
class ExpireOffers extends Command
{
    protected $signature = 'offers:expire';

    protected $description = 'Close offers whose expiry has passed';

    public function handle(OfferService $offers): int
    {
        $closed = $offers->expireLapsed();

        // Only speak when something happened. This runs every minute, and a
        // line of output per minute is how a scheduler log stops being read.
        if ($closed > 0) {
            $this->info("Expired {$closed} offer(s).");
        }

        return self::SUCCESS;
    }
}
