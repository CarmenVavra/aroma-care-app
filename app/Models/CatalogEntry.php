<?php

namespace App\Models;

use App\Enums\ProductCategory;
use App\Services\ProductLookup\ProductSuggestion;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared code catalogue: what a barcode / QR code stands for, learned from
 * products saved by any user. No user reference is stored, only product data.
 *
 * @property ProductCategory|null $category
 */
class CatalogEntry extends Model
{
    public const SOURCE = 'dem AromaCare-Katalog';

    protected $fillable = [
        'code',
        'name',
        'brand',
        'category',
        'quantity',
        'unit',
        'latin_name',
    ];

    protected function casts(): array
    {
        return [
            'category' => ProductCategory::class,
            'quantity' => 'decimal:2',
        ];
    }

    /**
     * Remember (or update) what this product's code stands for.
     * The package size (initial quantity) is stored, not the remaining amount.
     */
    public static function learnFrom(Product $product): void
    {
        if ($product->barcode === null || trim($product->barcode) === '') {
            return;
        }

        static::updateOrCreate(['code' => $product->barcode], [
            'name' => $product->name,
            'brand' => $product->brand,
            'category' => $product->category,
            'quantity' => $product->initial_quantity,
            'unit' => $product->unit,
            'latin_name' => $product->essentialOilDetail->latin_name ?? $product->carrierOilDetail?->latin_name,
        ]);
    }

    /**
     * @param  list<string>  $codes  equivalent spellings of one code (see Barcode::variants)
     */
    public static function suggestionFor(array $codes): ?ProductSuggestion
    {
        $entry = static::whereIn('code', $codes)->latest('updated_at')->first();

        return $entry === null ? null : new ProductSuggestion(
            source: self::SOURCE,
            name: $entry->name,
            brand: $entry->brand,
            category: $entry->category,
            quantity: $entry->quantity !== null ? (float) $entry->quantity : null,
            unit: $entry->unit,
            latinName: $entry->latin_name,
        );
    }
}
