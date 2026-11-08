<?php

namespace App\Models;

use App\Enums\ProductCategory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ProductCategory $category
 * @property array<int, string> $match_terms
 * @property-read string $formatted_amount
 */
class RecipeIngredient extends Model
{
    protected $fillable = [
        'position',
        'name',
        'category',
        'amount',
        'unit',
        'match_terms',
        'optional',
    ];

    protected function casts(): array
    {
        return [
            'category' => ProductCategory::class,
            'amount' => 'decimal:2',
            'match_terms' => 'array',
            'optional' => 'boolean',
        ];
    }

    /** @return BelongsTo<Recipe, $this> */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * e.g. "2 Tr." or "10 ml".
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::get(fn (): string => Product::formatNumber($this->amount).' '.$this->unit);
    }
}
