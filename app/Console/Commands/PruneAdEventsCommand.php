<?php

namespace App\Console\Commands;

use App\Models\AdEvent;
use Illuminate\Console\Command;

/**
 * Enforces the retention promise in the privacy policy.
 *
 * Section 8 tells visitors that advertising traffic records are kept for 24
 * months and then deleted, including the IP address recorded with them. This
 * is what makes that true. A published retention period with nothing deleting
 * anything is worse than no promise at all: it is a statement to every visitor
 * that is false, and it is the first claim a regulator checks.
 *
 * One window, not two. An earlier draft removed IP addresses at 90 days and
 * the rest of the row at 24 months, on the reasoning that reporting does not
 * need an address. Retention was set to a single 24 months instead, so there
 * is one promise to keep and one boundary to get right - and no possibility of
 * the policy describing two periods while the code applies one.
 *
 * Deletes in chunks so a large backlog cannot hold a lock long enough to be
 * felt by a visitor being recorded at the same moment.
 */
class PruneAdEventsCommand extends Command
{
    protected $signature = 'listora:prune-ad-events
        {--dry-run : Report what would be deleted without deleting it}';

    protected $description = 'Apply the published retention period to advertising traffic records';

    /**
     * Section 8 of the privacy policy.
     *
     * Changing this changes a public promise, and the policy needs a new
     * version if it moves.
     */
    private const RETAIN_MONTHS = 24;

    public function handle(): int
    {
        $cutoff = now()->subMonths(self::RETAIN_MONTHS);

        $expired = AdEvent::query()->where('occurred_at', '<', $cutoff)->count();

        $this->line(sprintf(
            'Older than %d months (before %s): %s %s.',
            self::RETAIN_MONTHS,
            $cutoff->format('j M Y'),
            number_format($expired),
            str('record')->plural($expired),
        ));

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing deleted.');

            return self::SUCCESS;
        }

        $deleted = 0;

        while (($affected = AdEvent::query()->where('occurred_at', '<', $cutoff)->limit(1000)->delete()) > 0) {
            $deleted += $affected;
        }

        $this->info(sprintf('Deleted %s %s.', number_format($deleted), str('record')->plural($deleted)));

        return self::SUCCESS;
    }
}
