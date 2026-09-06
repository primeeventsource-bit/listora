<?php

namespace App\Listeners;

use App\Enums\AdEventType;
use App\Services\Advertising\AdEventRecorder;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;

/**
 * Sign-in, sign-out and account creation, into the activity log.
 *
 * Hung off the framework's own auth events rather than written into the four
 * controllers that sign people in. Web login, API login, registration's
 * automatic sign-in and the sign-out inside account deletion all fire these,
 * and a listener catches every one of them - including the next one somebody
 * adds. Four call sites would have caught three of them and looked complete.
 *
 * AccountCreated was the gap worth closing: it has been in the funnel enum
 * since the start with nothing writing it, so "created an account" was
 * missing from the one timeline where it explains everything either side of
 * it - the visitor who read a listing, started an inquiry, registered, and
 * then submitted it.
 *
 * The recorder swallows its own failures. Nobody should be unable to sign in
 * because the note about it could not be written.
 */
class RecordAuthActivity
{
    public function __construct(private readonly AdEventRecorder $recorder)
    {
    }

    public function handleLogin(Login $event): void
    {
        $this->record(AdEventType::SignedIn);
    }

    public function handleLogout(Logout $event): void
    {
        $this->record(AdEventType::SignedOut);
    }

    public function handleRegistered(Registered $event): void
    {
        $this->record(AdEventType::AccountCreated);
    }

    private function record(AdEventType $type): void
    {
        $request = request();

        // Console commands and queued work have no request to describe, and a
        // row with no address, page or device says nothing an audit trail can
        // use. Seeders creating users are the common case.
        //
        // runningUnitTests() is excepted deliberately: PHPUnit also runs in
        // the console, so the bare check disabled recording in exactly the
        // place a missing recorder call has to be caught.
        if (! $request instanceof \Illuminate\Http\Request) {
            return;
        }

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $this->recorder->record($request, $type);
    }
}
