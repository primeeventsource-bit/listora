<?php

namespace App\Services\Seo;

use App\Models\Listing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Search and paid-search metadata for the Explore page.
 *
 * /browse is one route that answers to seven query parameters, so it is really
 * a few hundred thousand URLs wearing one coat. Left alone that is the classic
 * faceted-navigation problem: near-identical pages competing with each other,
 * crawl budget spent on sort orders, and paid traffic landing on a page whose
 * title says "Browse every listing" no matter which ad was clicked.
 *
 * This builds the per-URL answer to both halves:
 *
 *   SEO  — a title and description that name the actual facet, a canonical
 *          that folds away the parameters which only re-order or re-slice the
 *          same set, and a robots policy that lets the handful of genuinely
 *          distinct facet pages index while keeping the long tail out.
 *
 *   PPC  — the item payload Google Ads needs for a remarketing audience and
 *          GA4 needs for `view_item_list`, plus the landing-page relevance
 *          that decides Quality Score. An ad group for "vacation weeks Hawaii"
 *          pointing at a page headed "Browse every listing" is scored on that
 *          mismatch, and it is charged for it on every click.
 *
 * On structured data, note what is deliberately absent: no Product, no Offer,
 * no priceCurrency. Those types tell Google the thing is purchasable and can
 * surface it in merchant-style results with a buy intent attached. Listora
 * advertises — an asking price here is the owner's number, not a transaction
 * this site can complete. ItemList says "these are listings" and stops there,
 * which is the true statement. See CONTENT.md, "the one rule the copy has to
 * hold".
 */
final class ExploreSeo
{
    /**
     * Facets that name a real, distinct slice of inventory. A page built from
     * these is worth indexing and worth pointing an ad group at; anything else
     * in the query string is a view of one of these.
     */
    private const INDEXABLE_FACETS = ['kind', 'mode', 'region'];

    /**
     * Above this many facets combined, pages get thin and start duplicating
     * each other ("2-bed club points to rent in Mexico" will match the same
     * three listings as its parent). Follow them, do not index them.
     */
    private const MAX_INDEXABLE_FACETS = 2;

    public function __construct(
        private readonly array $filters,
        private readonly LengthAwarePaginator $listings,
    ) {
    }

    // ------------------------------------------------------------------- meta

    /**
     * Titles read as the facet, not as the page: "Vacation Weeks to Rent in
     * Hawaii — Listora". That string is what a searcher scans in the results
     * and what Google Ads compares the ad's headline against.
     */
    public function title(): string
    {
        $subject = $this->subject();
        $mode    = $this->modeFragment();
        $region  = $this->regionFragment();

        if ($this->keyword()) {
            return sprintf('%s — Listora', $this->keyword());
        }

        if (! $subject && ! $mode && ! $region) {
            return 'Vacation Properties — Listora';
        }

        return trim(sprintf(
            '%s%s%s — Listora',
            $subject ?: 'Vacation Properties',
            $mode,
            $region,
        ));
    }

    /**
     * The visible H1, and it has to agree with title().
     *
     * Google Ads scores landing-page relevance partly on whether the page says
     * what the ad said. Sending "vacation weeks in Hawaii" traffic to a page
     * headed "Browse every listing" is the cheapest available way to depress
     * Quality Score, and it is paid for on every click, not once.
     */
    public function heading(): string
    {
        if ($keyword = $this->keyword()) {
            return sprintf('Listings matching “%s”', $keyword);
        }

        $subject = $this->subject();
        $mode    = $this->modeFragment();
        $region  = $this->regionFragment();

        // Vacation properties are the only kind offered, so the unfiltered page
        // and ?kind=home now say the same thing — which is correct rather than
        // redundant. A facet page still names its own facet.
        if (! $subject && ! $mode && ! $region) {
            return 'Vacation Properties';
        }

        return trim(($subject ?: 'Vacation Properties').$mode.$region);
    }

    /**
     * Names the facet, then the live count, then the model.
     *
     * The count is the one thing a snippet can say that a competitor's
     * boilerplate cannot, but it is a separate clause rather than a prefix —
     * "1 vacation weeks" is what happens when a running total is bolted onto
     * a category name that is already plural. It also stays clear of the
     * ~155 characters Google will actually render.
     */
    public function description(): string
    {
        $total = $this->listings->total();

        $what = trim(
            ($this->subject() ?: 'Vacation properties')
            .$this->modeFragment(titleCase: false)
            .$this->regionFragment()
        );

        $count = $total > 0
            ? sprintf(' — %s live %s', number_format($total), Str::plural('listing', $total))
            : '';

        return sprintf(
            '%s%s, advertised directly by their owners. '
            .'One flat listing fee, no commission. Contact the owner yourself.',
            $what,
            $count,
        );
    }

    /**
     * Self-referencing, with `sort` and empty values folded away.
     *
     * Sort is the only parameter that returns a byte-identical result set in a
     * different order, so it is the one that must not mint a URL. Page stays:
     * page 3 holds different listings than page 1 and should canonicalise to
     * itself, not claim to be page 1.
     */
    public function canonical(): string
    {
        return route('listings.index', $this->canonicalParameters());
    }

    /** @return array<string, string|int> */
    private function canonicalParameters(): array
    {
        $params = [];

        foreach (['q', 'kind', 'mode', 'region', 'beds', 'max'] as $key) {
            $value = $this->filters[$key] ?? null;

            if ($value !== null && $value !== '' && $value !== 'all') {
                $params[$key] = $value;
            }
        }

        if ($this->listings->currentPage() > 1) {
            $params['page'] = $this->listings->currentPage();
        }

        return $params;
    }

    /**
     * `noindex, follow` — never `nofollow`. The long-tail facet pages should
     * stay out of the index while still passing crawlers through to the
     * listing detail pages underneath, which are the pages worth ranking.
     *
     * An empty result set is always noindex: a page that says "nothing matches
     * that yet" is a soft 404, and enough of them earn a site-wide quality
     * problem rather than a per-page one.
     */
    public function robots(): string
    {
        if (! (bool) setting('seo.robots_index', true)) {
            return 'noindex, follow';
        }

        $indexable = $this->listings->total() > 0
            && ! $this->keyword()
            && ! $this->hasNonDefaultSort()
            && $this->activeFacetCount() <= self::MAX_INDEXABLE_FACETS
            && ! $this->hasNonFacetFilter();

        return $indexable ? 'index, follow' : 'noindex, follow';
    }

    // ------------------------------------------------------- structured data

    /**
     * CollectionPage + ItemList + BreadcrumbList in one graph.
     *
     * Encoded with JSON_HEX_TAG so a listing title containing `</script>`
     * cannot break out of the block it is printed into.
     */
    public function jsonLd(): string
    {
        $graph = [
            [
                '@type'       => 'CollectionPage',
                '@id'         => $this->canonical(),
                'url'         => $this->canonical(),
                'name'        => $this->title(),
                'description' => $this->description(),
                'isPartOf'    => ['@type' => 'WebSite', 'name' => 'Listora', 'url' => url('/')],
            ],
            [
                '@type'           => 'BreadcrumbList',
                'itemListElement' => $this->breadcrumbTrail(),
            ],
        ];

        if ($this->listings->count()) {
            $graph[] = [
                '@type'            => 'ItemList',
                'name'             => $this->title(),
                'numberOfItems'    => $this->listings->total(),
                'itemListOrder'    => 'https://schema.org/ItemListOrderDescending',
                'itemListElement'  => $this->itemListElements(),
            ];
        }

        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP,
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function itemListElements(): array
    {
        $offset = ($this->listings->currentPage() - 1) * $this->listings->perPage();

        return $this->listings->getCollection()
            ->values()
            ->map(fn (Listing $listing, int $i) => [
                '@type'    => 'ListItem',
                'position' => $offset + $i + 1,
                'url'      => $listing->publicUrl(),
                'name'     => $listing->title,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function breadcrumbTrail(): array
    {
        $trail = [
            ['name' => 'Listora', 'url' => url('/')],
            ['name' => 'Explore', 'url' => route('listings.index')],
        ];

        if ($subject = $this->subject()) {
            $trail[] = [
                'name' => $subject,
                'url'  => route('listings.index', ['kind' => $this->filters['kind']]),
            ];
        }

        if ($region = $this->region()) {
            $trail[] = [
                'name' => $region,
                'url'  => route('listings.index', array_filter([
                    'kind'   => $this->activeKind(),
                    'region' => $region,
                ])),
            ];
        }

        return collect($trail)
            ->map(fn (array $crumb, int $i) => [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['name'],
                'item'     => $crumb['url'],
            ])
            ->all();
    }

    // ------------------------------------------------------------------- ppc

    /**
     * The `view_item_list` payload for gtag.
     *
     * Item ids are the listing reference (LST-xxxx), which is the identifier
     * an owner, an inquiry, and an admin record all already share — so a
     * remarketing audience assembled here can be reconciled against what the
     * database knows without inventing a second id space.
     *
     * `price` is the owner's asking figure, sent as-is. It is what the page
     * displays and what the visitor is responding to; it is not a checkout
     * value, and nothing downstream should treat it as revenue.
     *
     * @return array<string, mixed>
     */
    public function itemListPayload(): array
    {
        $listName = $this->title();

        return [
            'item_list_id'   => $this->itemListId(),
            'item_list_name' => $listName,
            'items'          => $this->listings->getCollection()
                ->values()
                ->map(fn (Listing $listing, int $i) => [
                    'item_id'        => $listing->reference,
                    'item_name'      => $listing->title,
                    'item_category'  => $listing->kind_label,
                    'item_category2' => $listing->mode === 'rent' ? 'Rent' : 'Own',
                    'item_category3' => $listing->region,
                    'item_brand'     => $listing->resort_name ?: $listing->club_name ?: 'Private owner',
                    'item_list_name' => $listName,
                    'index'          => $i + 1,
                    'price'          => round((float) $listing->price, 2),
                    'quantity'       => 1,
                ])
                ->all(),
        ];
    }

    /** Stable per-facet id, so Ads and GA4 can segment by which slice was seen. */
    public function itemListId(): string
    {
        $parts = array_filter([
            $this->activeKind() ?: 'all',
            $this->activeMode() ?: 'any',
            $this->region() ? Str::slug($this->region()) : 'anywhere',
        ]);

        return 'explore_'.implode('_', $parts);
    }

    // ------------------------------------------------------------ inspection

    private function subject(): string
    {
        $kind = $this->activeKind();

        return $kind ? (Listing::KINDS[$kind] ?? '') : '';
    }

    /**
     * Title case for a <title> and an <h1>, lower case mid-sentence. The same
     * fragment appears in both, and "to Rent" inside a meta description reads
     * like a typo rather than a heading.
     */
    private function modeFragment(bool $titleCase = true): string
    {
        return match ($this->activeMode()) {
            'rent'  => $titleCase ? ' to Rent' : ' to rent',
            'own'   => $titleCase ? ' to Own' : ' to own',
            default => '',
        };
    }

    private function regionFragment(): string
    {
        return $this->region() ? ' in '.$this->region() : '';
    }

    private function activeKind(): ?string
    {
        $kind = $this->filters['kind'] ?? null;

        return $kind && $kind !== 'all' && isset(Listing::KINDS[$kind]) ? $kind : null;
    }

    private function activeMode(): ?string
    {
        $mode = $this->filters['mode'] ?? null;

        return in_array($mode, ['rent', 'own'], true) ? $mode : null;
    }

    private function region(): ?string
    {
        $region = $this->filters['region'] ?? null;

        return $region && $region !== 'all' ? $region : null;
    }

    private function keyword(): ?string
    {
        $q = trim((string) ($this->filters['q'] ?? ''));

        return $q !== '' ? $q : null;
    }

    private function hasNonDefaultSort(): bool
    {
        $sort = $this->filters['sort'] ?? 'recommended';

        return $sort !== null && $sort !== '' && $sort !== 'recommended';
    }

    /** Bedrooms and max price slice a facet page rather than naming one. */
    private function hasNonFacetFilter(): bool
    {
        return ! empty($this->filters['beds']) || ! empty($this->filters['max']);
    }

    private function activeFacetCount(): int
    {
        return collect(self::INDEXABLE_FACETS)
            ->filter(function (string $facet) {
                $value = $this->filters[$facet] ?? null;

                return $value !== null && $value !== '' && $value !== 'all';
            })
            ->count();
    }
}
