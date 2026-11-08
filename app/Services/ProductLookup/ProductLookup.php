<?php

namespace App\Services\ProductLookup;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Looks up product data for a barcode in public product databases.
 *
 * Only the barcode number leaves the server (no user data, no IP address of
 * the user). Results are cached, so a barcode is asked for at most once a
 * month, and a miss at most twice a day.
 */
class ProductLookup
{
    public const SOURCES = [
        'open_beauty_facts' => 'Open Beauty Facts',
        'open_food_facts' => 'Open Food Facts',
        'upcitemdb' => 'UPCitemdb',
    ];

    public function __construct(private ProductTitleParser $parser) {}

    /**
     * @param  list<string>  $codes  equivalent spellings of one barcode (see Barcode::variants)
     */
    public function find(array $codes): ?ProductSuggestion
    {
        if (! config('aromacare.lookup.enabled') || $codes === []) {
            return null;
        }

        $key = 'product-lookup:v1:'.$codes[0];
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return ProductSuggestion::fromArray($cached);
        }

        if ($cached === false) {
            return null; // recent miss
        }

        $reachedAll = true;

        foreach ((array) config('aromacare.lookup.sources') as $source) {
            foreach ($codes as $code) {
                try {
                    $suggestion = $this->query((string) $source, $code);
                } catch (ConnectionException $e) {
                    Log::info("Product lookup: {$source} not reachable", ['message' => $e->getMessage()]);
                    $reachedAll = false;

                    continue 2;
                }

                if ($suggestion?->hasName()) {
                    Cache::put($key, $suggestion->toArray(), now()->addDays(30));

                    return $suggestion;
                }
            }
        }

        // Only remember a miss if every database actually answered.
        if ($reachedAll) {
            Cache::put($key, false, now()->addHours(12));
        }

        return null;
    }

    private function query(string $source, string $code): ?ProductSuggestion
    {
        return match ($source) {
            'open_beauty_facts' => $this->openFacts('https://world.openbeautyfacts.org', $code, self::SOURCES[$source]),
            'open_food_facts' => $this->openFacts('https://world.openfoodfacts.org', $code, self::SOURCES[$source]),
            'upcitemdb' => $this->upcItemDb($code),
            default => null,
        };
    }

    private function openFacts(string $baseUrl, string $code, string $label): ?ProductSuggestion
    {
        $response = $this->http()->get("{$baseUrl}/api/v2/product/".rawurlencode($code), [
            'fields' => 'product_name,product_name_de,generic_name_de,brands,quantity',
        ]);

        if (! $response->successful() || (int) $response->json('status') !== 1) {
            return null;
        }

        $product = (array) $response->json('product');
        $title = $product['product_name_de'] ?? null ?: ($product['product_name'] ?? null) ?: ($product['generic_name_de'] ?? null);

        return $this->parser->parse($label, $title, $product['brands'] ?? null, $product['quantity'] ?? null);
    }

    private function upcItemDb(string $code): ?ProductSuggestion
    {
        // Free trial endpoint: max. 100 requests per day, no API key required.
        $response = $this->http()->get('https://api.upcitemdb.com/prod/trial/lookup', ['upc' => $code]);

        if ($response->status() === 429) {
            throw new ConnectionException('UPCitemdb daily limit reached');
        }

        $item = $this->firstItem($response);

        return $item === null ? null : $this->parser->parse(self::SOURCES['upcitemdb'], $item['title'] ?? null, $item['brand'] ?? null, $item['size'] ?? null);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function firstItem(Response $response): ?array
    {
        if (! $response->successful()) {
            return null;
        }

        $item = $response->json('items.0');

        return is_array($item) ? $item : null;
    }

    private function http(): PendingRequest
    {
        return Http::timeout((int) config('aromacare.lookup.timeout'))
            ->connectTimeout(3)
            ->acceptJson()
            ->withUserAgent(config('app.name').'/1.0 (+'.config('app.url').')');
    }
}
