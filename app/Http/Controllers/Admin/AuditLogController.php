<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Activity Log — the read half of the audit trail.
 *
 * `AdminAuditLogService::log()` has been writing a row for every privileged
 * write since the table shipped, and `audit.view` has been gating a screen
 * that did not exist. So the log was write-only: the console recorded who
 * changed what, and nobody could ever look. That is the same failure the
 * Inbox docblock argues against, applied to the one dataset whose entire
 * purpose is being read later.
 *
 * Read-only by construction. There is no edit action, no delete action, and no
 * route to add one — an audit trail an admin can amend is not evidence of
 * anything. Rows are immutable at the model too (`$timestamps = false`, and
 * `occurred_at` is set once by the writer).
 */
class AuditLogController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): View
    {
        $filters = [
            'actor' => $request->query('actor'),
            'action' => $request->query('action'),
            'q' => trim((string) $request->query('q')),
            'days' => $request->query('days', '30'),
        ];

        $query = AdminAuditLog::query()
            ->with('actor:id,name,email')
            ->latest('occurred_at');

        if ($filters['actor']) {
            $query->where('actor_user_id', $filters['actor']);
        }

        if ($filters['action']) {
            $query->where('action', $filters['action']);
        }

        // Bounded by default. An unbounded audit view is the one screen
        // guaranteed to get slower every single day it is left running.
        if ($filters['days'] !== 'all' && is_numeric($filters['days'])) {
            $query->where('occurred_at', '>=', now()->subDays((int) $filters['days']));
        }

        // Free text spans the subject and the payload, because "what happened
        // to LST-D-4H2K9M" is the question this screen actually gets asked —
        // and the reference lives inside the JSON payload, not in a column.
        if ($filters['q'] !== '') {
            $term = '%'.str_replace('%', '', $filters['q']).'%';

            $query->where(function ($w) use ($term) {
                $w->where('subject_type', 'like', $term)
                    ->orWhere('subject_id', 'like', $term)
                    ->orWhere('payload', 'like', $term)
                    ->orWhere('ip_address', 'like', $term);
            });
        }

        return view('admin.audit.index', [
            'entries' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'filters' => $filters,
            'actions' => $this->knownActions(),
            'actors' => $this->knownActors(),
            'total' => AdminAuditLog::count(),
        ]);
    }

    /**
     * Show one entry with its full payload.
     *
     * Separate from the index because a payload can be large and printing
     * every one inline turns the list into something nobody can scan.
     */
    public function show(AdminAuditLog $entry): View
    {
        $entry->load('actor:id,name,email,role');

        return view('admin.audit.show', [
            'entry' => $entry,
            // Other things the same actor did around the same moment. An audit
            // entry read alone rarely answers the question that opened it.
            'nearby' => AdminAuditLog::query()
                ->with('actor:id,name')
                ->where('id', '!=', $entry->id)
                ->where('actor_user_id', $entry->actor_user_id)
                ->whereBetween('occurred_at', [
                    $entry->occurred_at->copy()->subMinutes(30),
                    $entry->occurred_at->copy()->addMinutes(30),
                ])
                ->latest('occurred_at')
                ->limit(10)
                ->get(),
        ]);
    }

    /** @return list<string> Distinct action keys actually present, for the filter. */
    private function knownActions(): array
    {
        return AdminAuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }

    /** Only users who have actually done something — not the whole user table. */
    private function knownActors()
    {
        return User::query()
            ->whereIn('id', AdminAuditLog::query()->select('actor_user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
