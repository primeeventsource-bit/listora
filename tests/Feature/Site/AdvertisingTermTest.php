<?php

namespace Tests\Feature\Site;

use App\Enums\PlanTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The advertising term is 180 days.
 *
 * Expressed in days rather than months on purpose. A term of "six months"
 * drifts against the calendar - two listings published a day apart can get
 * terms of different lengths depending on which months they cross - and an
 * advertiser who paid the same fee should get the same number of days.
 *
 * The term was previously twelve months in the code and described as "twelve
 * months", "a full year" and "12 months" across the site, so this pins the
 * value and the claim together.
 */
class AdvertisingTermTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_plan_runs_for_one_hundred_and_eighty_days(): void
    {
        foreach (PlanTier::cases() as $plan) {
            $this->assertSame(180, $plan->termDays(), "{$plan->value} should run 180 days.");
        }
    }

    public function test_the_setting_agrees_with_the_plans(): void
    {
        $this->assertSame(180, (int) setting('listings.default_term_days', 180));
    }

    /** 180 days from a fixed date, checkable by hand. */
    public function test_the_end_date_is_exactly_one_hundred_and_eighty_days_out(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 12:00:00'));

        $this->assertSame(
            '2027-03-01',
            now()->addDays(PlanTier::Starter->termDays())->toDateString(),
        );

        Carbon::setTestNow();
    }

    /**
     * The site must not still promise a year. An advertiser reading "twelve
     * months" on the pricing page and getting 180 days has been misled about
     * the thing they are buying.
     */
    public function test_no_public_page_still_promises_twelve_months(): void
    {
        $claims = ['twelve months', '12 months', '12 full months', 'a full year', 'runs a year'];
        $found = [];

        foreach (['/', '/pricing', '/how-it-works', '/about', '/list-your-property'] as $page) {
            $html = strtolower($this->get($page)->getContent());

            foreach ($claims as $claim) {
                if (str_contains($html, $claim)) {
                    $found[] = "{$page} still says \"{$claim}\"";
                }
            }
        }

        $this->assertSame([], $found, implode(PHP_EOL, $found));
    }
}
