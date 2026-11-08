<?php

namespace App\Services\ProductLookup;

use App\Enums\ProductCategory;
use App\Support\EssentialOilNames;
use Illuminate\Support\Str;

/**
 * Turns free-text product data from public databases into form values,
 * e.g. "doTERRA Lavender Essential Oil - 15 mL" →
 * name "Lavendel", brand "doTERRA", 15 ml, essential oil, Lavandula angustifolia.
 */
class ProductTitleParser
{
    private const ESSENTIAL = '/essential oil|ätherisch|aetherisch|huile essentielle|olio essenziale|aceite esencial|naturreines öl/iu';

    private const CARRIER = '/carrier oil|basisöl|trägeröl|jojoba|mandelöl|almond oil|argan|coconut oil|kokosöl|avocado|aprikosenkern|apricot kernel|nachtkerze|hagebutte|rosehip|shea|sheabutter|karité|kakaobutter|cocoa butter/iu';

    private const WAX = '/beeswax|bienenwachs|candelilla|carnauba|\bwachs\b|\bwax\b/iu';

    public function parse(string $source, ?string $title, ?string $brand = null, ?string $quantity = null): ProductSuggestion
    {
        $title = trim((string) $title);
        $brand = $this->cleanBrand($brand);
        $text = $title.' '.$quantity;

        $oil = EssentialOilNames::find($title);
        $category = $this->category($title, $oil !== null);
        [$amount, $unit] = $this->quantity((string) $quantity) ?? $this->quantity($title) ?? [null, null];

        $name = $category === ProductCategory::EssentialOil && $oil !== null
            ? $oil['name']
            : $this->cleanName($title, $brand);

        return new ProductSuggestion(
            source: $source,
            name: $name !== '' ? Str::limit($name, 117) : null,
            brand: $brand,
            category: $category,
            quantity: $amount,
            unit: $unit ?? ($category === ProductCategory::Wax ? 'g' : null),
            latinName: $category === ProductCategory::EssentialOil ? ($oil['latin'] ?? null) : null,
        );
    }

    private function category(string $title, bool $knownOil): ?ProductCategory
    {
        return match (true) {
            (bool) preg_match(self::ESSENTIAL, $title) => ProductCategory::EssentialOil,
            (bool) preg_match(self::WAX, $title) => ProductCategory::Wax,
            (bool) preg_match(self::CARRIER, $title) => ProductCategory::CarrierOil,
            $knownOil => ProductCategory::EssentialOil,
            default => null,
        };
    }

    /**
     * "15 mL", "5ml", "0.5 fl oz", "30 g" → [amount, unit] in the app's units.
     *
     * @return array{0: float, 1: string}|null
     */
    private function quantity(string $text): ?array
    {
        if (! preg_match('/(\d+(?:[.,]\d+)?)\s*(ml|g|fl\.?\s*oz|oz)\b/iu', $text, $m)) {
            return null;
        }

        $amount = (float) str_replace(',', '.', $m[1]);
        $unit = Str::lower((string) preg_replace('/[\s.]/', '', $m[2]));

        return match ($unit) {
            'ml' => [$amount, 'ml'],
            'g' => [$amount, 'g'],
            'floz' => [round($amount * 29.5735), 'ml'],
            'oz' => [round($amount * 28.3495), 'g'],
            default => null,
        };
    }

    private function cleanBrand(?string $brand): ?string
    {
        // Open Food Facts lists several brands comma-separated: take the first.
        $brand = trim(explode(',', (string) $brand)[0]);

        return $brand !== '' ? Str::limit($brand, 117) : null;
    }

    private function cleanName(string $title, ?string $brand): string
    {
        $name = $title;

        if ($brand !== null) {
            $name = (string) preg_replace('/'.preg_quote($brand, '/').'/iu', '', $name);
        }

        $name = (string) preg_replace([
            '/\d+(?:[.,]\d+)?\s*(ml|g|fl\.?\s*oz|oz)\b/iu',
            '/\b(essential oil|ätherisches öl|100\s*%\s*(pure|naturrein)?)\b/iu',
            '/\s*[-–|,]\s*$/u',
            '/^\s*[-–|,]\s*/u',
            '/\s{2,}/u',
        ], ['', '', '', '', ' '], $name);

        $name = trim($name, " \t-–|,");

        // Title consisted only of brand and size (e.g. "Nutella"): keep the original title.
        return $name !== '' ? $name : trim($title);
    }
}
