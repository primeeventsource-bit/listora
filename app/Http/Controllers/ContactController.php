<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;

/**
 * The "ask a question" form that lives on the Help page.
 *
 * A real form that persists, not a mailto: link — the old Contact tab was
 * exactly that, and a mailto opens a blank email client with no answers in
 * between and no record on our side that anyone ever asked.
 *
 * Submissions land in `contact_messages` where staff holding `inbox.view` can
 * work them, and the customer gets a reference they can quote back.
 *
 * There is no show() any more: the form is rendered by HelpController and
 * /contact permanently redirects to /help#ask.
 */
class ContactController extends Controller
{
    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $message = ContactMessage::create([
            'first_name' => $request->validated('first_name'),
            'last_name' => $request->validated('last_name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'department' => $request->validated('department'),
            'subject' => $request->validated('subject'),
            'message' => $request->validated('message'),
            'status' => ContactMessage::STATUS_NEW,
            'source_url' => substr((string) $request->headers->get('referer', ''), 0, 500) ?: null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Back to the form's actual home, anchored so the confirmation is on
        // screen rather than four sections above the fold.
        return redirect()
            ->to(route('help.index').'#ask')
            ->with('contact_reference', $message->reference)
            ->with('contact_success', "Thanks — we've got your message and will reply by email.");
    }
}
