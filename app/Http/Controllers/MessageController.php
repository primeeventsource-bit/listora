<?php

namespace App\Http\Controllers;

use App\Enums\AdEventType;
use App\Models\Conversation;
use App\Services\Advertising\AdEventRecorder;
use App\Services\Messaging\ConversationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The message inbox, for both sides of a conversation.
 *
 * One controller rather than an owner version and a visitor version. A thread
 * looks the same from both ends - your messages on one side, theirs on the
 * other - and the only difference is which party you are, which the model
 * already answers. Two controllers would be two places to get the
 * authorisation check right.
 */
class MessageController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly AdEventRecorder $recorder,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->forUser($user->id)
            ->with(['listing:id,slug,title,ad_number', 'owner:id,name', 'visitor:id,name'])
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(25);

        return view('messages.index', [
            'conversations' => $conversations,
            'me' => $user,
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $user = $request->user();

        $this->authorizeParticipant($conversation, $request);

        $conversation->load(['messages.sender:id,name', 'listing:id,slug,title,ad_number']);
        $conversation->markReadFor($user);

        return view('messages.show', [
            'conversation' => $conversation,
            'counterpart' => $conversation->counterpartFor($user),
            'me' => $user,
        ]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();

        $this->authorizeParticipant($conversation, $request);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        $isFirst = $conversation->messages()->count() === 0;

        $this->conversations->post($conversation, $user, $data['body']);

        // The last step of the advertising funnel: interest became a
        // conversation. Only the first message counts - every reply after it
        // is the same conversation continuing.
        if ($isFirst) {
            $this->recorder->record($request, AdEventType::MessageStarted, $conversation->listing);
        }

        return redirect()
            ->route('messages.show', $conversation)
            ->with('sent', 'Sent. Listora passes it straight on and stays out of the conversation.');
    }

    /**
     * A thread belongs to exactly two people.
     *
     * 404 rather than 403: telling a stranger "you may not see conversation
     * 47" confirms that conversation 47 exists and that they are not part of
     * it, which is more than they should learn from a URL.
     */
    private function authorizeParticipant(Conversation $conversation, Request $request): void
    {
        if (! $conversation->includes($request->user())) {
            throw new NotFoundHttpException;
        }
    }
}
