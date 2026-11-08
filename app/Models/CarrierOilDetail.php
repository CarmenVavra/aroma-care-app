<?php

namespace App\Models;

use App\Enums\Absorption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Absorption|null $absorption
 */
class CarrierOilDetail extends Model
{
    protected $fillable = [
        'latin_name',
        'certification',
        'comedogenic_rating',
        'absorption',
        'use_areas',
    ];

    protected function casts(): array
    {
        return [
            'comedogenic_rating' => 'integer',
            'absorption' => Absorption::class,
            'use_areas' => 'array',
        ];
    }

    /**
     * Word scale per design handoff (0 = not comedogenic … 5 = very high).
     */
    public function comedogenicLabel(): ?string
    {
        return match ($this->comedogenic_rating) {
            0 => 'nicht komedogen',
            1 => 'sehr gering',
            2 => 'gering',
            3 => 'mäßig',
            4 => 'hoch',
            5 => 'sehr hoch',
            default => null,
        };
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
