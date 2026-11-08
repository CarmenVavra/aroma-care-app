<?php

namespace App\Services\ProductLookup;

use App\Enums\ProductCategory;
use App\Support\Barcode;
use Illuminate\Support\Str;

/**
 * Hints derived from the code itself when no database knows the product:
 *
 * - Article numbers / company prefixes of known brands, e.g. doTERRA bottles
 *   carry 8-digit item numbers "602xxxxx" instead of an EAN, Naissance uses the
 *   GS1 company prefix 5055416.
 * - QR codes with a link: brand from the domain, product name from the path
 *   (e.g. "https://www.doterra.com/…/p/breathe-oil" → doTERRA, "Breathe").
 *
 * Nothing is fetched from the internet here.
 */
class CodeHints
{
    public const SOURCE = 'dem Code';

    /**
     * Code pattern → [brand, category or null].
     *
     * @var array<string, array{0: string, 1: ProductCategory|null}>
     */
    private const PATTERNS = [
        '/^602\d{5}$/' => ['doTERRA', ProductCategory::EssentialOil],
        '/^5055416\d{6}$/' => ['Naissance', null],
    ];

    /** Domain (second-level label) → brand as written by the manufacturer. */
    private const DOMAINS = [
        'doterra' => 'doTERRA',
        'sourcetoyou' => 'doTERRA',
        'naissance' => 'Naissance',
        'primavera' => 'Primavera',
        'youngliving' => 'Young Living',
        'farfalla' => 'Farfalla',
        'taoasis' => 'Taoasis',
    ];

    /** Path segments that never are a product name. */
    private const NOISE = [
        'p', 'pd', 'product', 'products', 'produkt', 'produkte', 'shop', 'store', 'item', 'items',
        'en', 'de', 'at', 'us', 'uk', 'eu', 'gb', 'int', 'www', 'index', 'html', 'php', 'qr', 'lot',
        'essential', 'oils', 'oil', 'blend', 'blends', 'single', 'singles', 'collection', 'collections',
    ];

    public function forCode(string $code): ?ProductSuggestion
    {
        foreach (self::PATTERNS as $pattern => [$brand, $category]) {
            if (preg_match($pattern, $code)) {
                return new ProductSuggestion(source: self::SOURCE, brand: $brand, category: $category, unit: $category ? 'ml' : null);
            }
        }

        return Barcode::isUrl($code) ? $this->fromUrl($code) : null;
    }

    private function fromUrl(string $url): ?ProductSuggestion
    {
        $parts = parse_url(preg_match('#^https?://#i', $url) ? $url : 'https://'.$url);
        $host = Str::lower((string) ($parts['host'] ?? ''));
        $labels = explode('.', $host);
        $domain = $labels[max(0, count($labels) - 2)] ?? '';

        $brand = self::DOMAINS[$domain] ?? null;
        $name = $this->nameFromPath((string) ($parts['path'] ?? ''));

        if ($brand === null && $name === null) {
            return null;
        }

        return new ProductSuggestion(
            source: self::SOURCE,
            name: $name,
            brand: $brand,
            category: $brand === 'doTERRA' ? ProductCategory::EssentialOil : null,
        );
    }

    /**
     * Last readable path segment, e.g. "breathe-oil" → "Breathe". Random tokens
     * (letters mixed with digits) are ignored.
     */
    private function nameFromPath(string $path): ?string
    {
        $segments = array_reverse(array_filter(explode('/', $path)));

        foreach ($segments as $segment) {
            $segment = Str::lower(rawurldecode((string) preg_replace('/\.\w{2,4}$/', '', $segment)));

            if (! preg_match('/^[\p{L}]+(?:[-_ ][\p{L}]+)*$/u', $segment)) {
                continue;
            }

            $words = array_values(array_filter(
                preg_split('/[-_ ]+/', $segment) ?: [],
                fn (string $word) => ! in_array($word, self::NOISE, true),
            ));

            if ($words !== [] && mb_strlen(implode('', $words)) >= 3) {
                return Str::limit(Str::title(implode(' ', $words)), 117);
            }
        }

        return null;
    }
}
