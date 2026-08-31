<?php

namespace Tests\Feature\Messaging;

use App\Enums\AdEventType;
use App\Enums\UserRole;
use App\Models\AdEvent;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use App\Services\Messaging\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Peer-to-peer conversations.
 *
 * A thread is private to exactly two people, and the failure mode is silent:
 * a broken check shows a stranger somebody's negotiation and looks completely
 * normal doing it. That is what most of this file is for.
 */
class ConversationTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create(['role' => UserRole::Owner, 'email_verified_at' => now()]);
    }

    private function visitor(): User
    {
        return User::factory()->create(['role' => UserRole::Traveler, 'email_verified_at' => now()]);
    }

    private function thread(): array
    {
        $owner = $this->owner();
        $visitor = $this->visitor();
        $listing = Listing::factory()->create(['owner_id' => $owner->id]);

        $conversation = app(ConversationService::class)->between($listing, $visitor);

        return [$conversation, $owner, $visitor, $listing];
    }

    public function test_a_signed_in_visitors_inquiry_opens_a_conversation(): void
    {
        $owner = $this->owner();
        $visitor = $this->visitor();
        $listing = Listing::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($visitor)->post("/listing/{$listing->slug}/inquire", [
            'name' => $visitor->name,
            'email' => $visitor->email,
            'message' => 'Is the first week of March still being advertised?',
        ])->assertRedirect();

        $this->assertDatabaseHas('conversations', [
            'listing_id' => $listing->id,
            'owner_user_id' => $owner->id,
            'visitor_user_id' => $visitor->id,
        ]);
    }

    /**
     * A guest inquiry must still work. It simply produces no thread, because
     * there is no account to carry the other half of one.
     */
    public function test_a_guest_inquiry_still_sends_and_opens_no_conversation(): void
    {
        $listing = Listing::factory()->create(['owner_id' => $this->owner()->id]);

        $this->post("/listing/{$listing->slug}/inquire", [
            'name' => 'Unregistered Visitor',
            'email' => 'guest@example.test',
            'message' => 'Could you tell me more about the resort itself?',
        ])->assertRedirect();

        $this->assertDatabaseHas('inquiries', ['email' => 'guest@example.test']);
        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_inquiring_twice_continues_one_conversation(): void
    {
        [$conversation, , $visitor, $listing] = $this->thread();

        app(ConversationService::class)->between($listing, $visitor);

        $this->assertSame(1, Conversation::query()->count());
        $this->assertSame($conversation->id, Conversation::query()->first()->id);
    }

    public function test_both_parties_can_read_and_reply(): void
    {
        [$conversation, $owner, $visitor] = $this->thread();

        $this->actingAs($visitor)
            ->post(route('messages.store', $conversation), ['body' => 'Are those dates still open?'])
            ->assertRedirect();

        $this->actingAs($owner)
            ->get(route('messages.show', $conversation))
            ->assertOk()
            ->assertSee('Are those dates still open?');

        $this->actingAs($owner)
            ->post(route('messages.store', $conversation), ['body' => 'They are — happy to discuss.'])
            ->assertRedirect();

        $this->actingAs($visitor)
            ->get(route('messages.show', $conversation))
            ->assertOk()
            ->assertSee('happy to discuss');
    }

    /**
     * The check that matters. 404, not 403 - telling a stranger they may not
     * see conversation 47 confirms both that it exists and that they are not
     * in it.
     */
    public function test_a_stranger_cannot_read_or_post_to_a_conversation(): void
    {
        [$conversation] = $this->thread();
        $stranger = $this->visitor();

        $this->actingAs($stranger)->get(route('messages.show', $conversation))->assertNotFound();

        $this->actingAs($stranger)
            ->post(route('messages.store', $conversation), ['body' => 'Let me in please'])
            ->assertNotFound();

        $this->assertSame(0, $conversation->messages()->count());
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        [$conversation] = $this->thread();

        $this->get(route('messages.show', $conversation))->assertRedirect('/login');
    }

    public function test_the_first_message_records_the_funnel_step_and_replies_do_not(): void
    {
        [$conversation, , $visitor] = $this->thread();

        $this->actingAs($visitor)
            ->post(route('messages.store', $conversation), ['body' => 'Opening the conversation.']);

        $this->actingAs($visitor)
            ->post(route('messages.store', $conversation), ['body' => 'And a second thought.']);

        $this->assertSame(
            1,
            AdEvent::query()->where('event_type', AdEventType::MessageStarted->value)->count(),
            'A conversation starts once; replies continue it.'
        );
    }

    public function test_a_thread_is_unread_for_the_recipient_and_not_the_sender(): void
    {
        [$conversation, $owner, $visitor] = $this->thread();

        $this->actingAs($visitor)
            ->post(route('messages.store', $conversation), ['body' => 'A question for you.']);

        $conversation->refresh();

        $this->assertTrue($conversation->isUnreadFor($owner));
        $this->assertFalse($conversation->isUnreadFor($visitor));

        $this->actingAs($owner)->get(route('messages.show', $conversation))->assertOk();

        $this->assertFalse($conversation->fresh()->isUnreadFor($owner));
    }

    /** An owner inquiring on their own listing would talk to themselves. */
    public function test_no_conversation_is_opened_with_yourself(): void
    {
        $owner = $this->owner();
        $listing = Listing::factory()->create(['owner_id' => $owner->id]);

        $this->assertNull(app(ConversationService::class)->between($listing, $owner));
    }

    /** Ownerless listings exist; they have nobody to hold the other side. */
    public function test_an_ownerless_listing_opens_no_conversation(): void
    {
        $listing = Listing::factory()->create(['owner_id' => null]);

        $this->assertNull(app(ConversationService::class)->between($listing, $this->visitor()));
    }
}
