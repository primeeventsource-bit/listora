<?php

namespace Tests\Feature\Site;

use App\Enums\PlanTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The published plans.
 *
 * A pricing page is the page a visitor is most likely to act on and the page
 * an underwriter reads first, so what it says is pinned here rather than left
 * to whoever edits the config next. The prices, the property counts and the
 * term all appear in more than one place - the page, the home page, the help
 * centre - and this is what stops those three from drifting apart.
 */
class PricingPlansTest extends TestCase
{
    use RefreshDatabase;

    /** [key, name, price, badge, properties] */
    public static function plans(): array
    {
        return [
            'starter' => ['starter', 'Starter', '$995', 'Ideal for beginners', 1],
            'explorer' => ['explorer', 'Explorer', '$1,995', 'Most popular', 3],
            'signature' => ['signature', 'Signature', '$3,995', 'Best value', 5],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('plans')]
    public function test_each_plan_is_published_as_priced(
        string $key,
        string $name,
        string $price,
        string $badge,
        int $properties,
    ): void {
        $html = $this->get('/pricing')->assertOk()->getContent();

        $this->assertStringContainsString($name, $html);
        $this->assertStringContainsString($price, $html);
        $this->assertStringContainsString($badge, $html);
        $this->assertStringContainsString('180 day listing', $html);
        $this->assertStringContainsString('Billed upfront', $html);

        $this->assertSame($properties, PlanTier::from($key)->propertyLimit());
    }

    /**
     * The artwork these plans came from had "Everything in Explorer, plus:"
     * on the Explorer card and "Everything in Signature, plus:" on the
     * Signature card - each one listing itself. A plan cannot include
     * everything in itself, and a buyer comparing three cards has no way to
     * tell what the middle one adds if the heading is wrong.
     */
    public function test_no_plan_says_it_includes_itself(): void
    {
        $plans = config('listora.plans');

        foreach ($plans as $plan) {
            $this->assertStringNotContainsString(
                $plan['name'],
                $plan['heading'],
                "The {$plan['name']} card says \"{$plan['heading']}\".",
            );
        }

        $this->assertSame('Everything in Starter, plus:', $plans['explorer']['heading']);
        $this->assertSame('Everything in Explorer, plus:', $plans['signature']['heading']);
    }

    /** Every config key must cast to a plan, or the CTA link 404s the wizard. */
    public function test_every_configured_plan_is_a_real_tier(): void
    {
        foreach (array_keys(config('listora.plans')) as $key) {
            $this->assertNotNull(PlanTier::tryFrom($key), "No PlanTier for '{$key}'.");
        }

        $this->assertSame(
            array_map(fn (PlanTier $p) => $p->value, PlanTier::cases()),
            array_keys(config('listora.plans')),
            'The plans are shown in the order the enum declares them.',
        );
    }

    /** The old names must not survive anywhere a visitor can read them. */
    public function test_the_retired_plan_names_are_gone(): void
    {
        $found = [];

        foreach (['/', '/pricing', '/list-your-property'] as $page) {
            $html = $this->get($page)->assertOk()->getContent();

            foreach (['Essential', 'Premier plan', 'per week listing'] as $old) {
                if (str_contains($html, $old)) {
                    $found[] = "{$page} still says \"{$old}\"";
                }
            }
        }

        $this->assertSame([], $found, implode(PHP_EOL, $found));
    }

    /** Icons are drawn inline, so a blocked CDN cannot empty the page. */
    public function test_the_plan_icons_need_no_third_party_request(): void
    {
        $html = $this->get('/pricing')->assertOk()->getContent();

        preg_match_all('/<img[^>]+src="(http[^"]+)"/i', $html, $m);

        foreach ($m[1] ?? [] as $src) {
            $this->assertStringNotContainsString('google', strtolower($src));
            $this->assertStringNotContainsString('facebook', strtolower($src));
            $this->assertStringNotContainsString('tiktok', strtolower($src));
        }
    }
}
