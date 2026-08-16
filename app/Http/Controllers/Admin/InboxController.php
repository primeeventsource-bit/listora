<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\JobApplication;
use App\Models\SupportTicket;
use App\Services\AdminAuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The back office for everything the public forms produce.
 *
 * Without this, the Help page's question form would be write-only — the exact
 * failure worth guarding against, where a form "works" because it accepts a
 * submission but nobody can ever act on it.
 *
 * There is deliberately no "hosting enquiries" tab. Vaytoven had one because
 * an owner asking to be advertised arrived as a generic enquiry; on Listora
 * that submission is a ListingDraft with its own review queue and its own
 * verification workflow (see Admin\DraftController). Surfacing drafts here as
 * well would give the same record two homes and two half-worked states.
 */
class InboxController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString() ?: 'contact';

        return view('admin.inbox.index', [
            'tab' => $tab,
            'contactMessages' => $tab === 'contact'
                ? ContactMessage::query()->latest()->paginate(self::PER_PAGE)->withQueryString()
                : null,
            'tickets' => $tab === 'support'
                ? SupportTicket::query()
                    ->where('source', SupportTicket::SOURCE_TRIP_SUPPORT)
                    ->with('openedBy:id,name,email')
                    ->latest()->paginate(self::PER_PAGE)->withQueryString()
                : null,
            'applications' => $tab === 'applications'
                ? JobApplication::query()->with('opening:id,title,slug')
                    ->latest()->paginate(self::PER_PAGE)->withQueryString()
                : null,
            'counts' => [
                'contact' => ContactMessage::query()->where('status', ContactMessage::STATUS_NEW)->count(),
                'support' => SupportTicket::query()
                    ->where('source', SupportTicket::SOURCE_TRIP_SUPPORT)
                    ->where('status', 'open')->count(),
                'applications' => JobApplication::query()->where('status', 'new')->count(),
            ],
        ]);
    }

    /** Mark a contact message as dealt with, so the queue drains. */
    public function markHandled(Request $request, ContactMessage $message): RedirectResponse
    {
        $message->update([
            'status' => ContactMessage::STATUS_HANDLED,
            'handled_by_user_id' => $request->user()->id,
            'handled_at' => now(),
        ]);

        AdminAuditLogService::log(
            actor: $request->user(),
            action: 'contact.handled',
            subject: $message,
            payload: ['reference' => $message->reference, 'department' => $message->department->value],
            ipAddress: $request->ip(),
        );

        return back()->with('success', "Marked {$message->reference} as handled.");
    }
}
