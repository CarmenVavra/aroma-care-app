<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EssentialOilDetail extends Model
{
    protected $fillable = [
        'latin_name',
        'effects_mental',
        'effects_physical',
        'usage_types',
        'dilution_hint',
    ];

    protected function casts(): array
    {
        return [
            'effects_mental' => 'array',
            'effects_physical' => 'array',
            'usage_types' => 'array',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
