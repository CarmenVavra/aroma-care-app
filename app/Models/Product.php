<?php

namespace App\Models;

use App\Enums\ProductCategory;
use App\Enums\StockStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property ProductCategory $category
 * @property Carbon|null $expires_at
 * @property-read int $fill_percent
 * @property-read StockStatus $stock_status
 * @property-read string $initials
 * @property-read string $formatted_quantity
 * @property-read string $formatted_initial_quantity
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'brand',
        'barcode',
        'category',
        'quantity',
        'initial_quantity',
        'unit',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => ProductCategory::class,
            'quantity' => 'decimal:2',
            'initial_quantity' => 'decimal:2',
            'expires_at' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<EssentialOilDetail, $this> */
    public function essentialOilDetail(): HasOne
    {
        return $this->hasOne(EssentialOilDetail::class);
    }

    /** @return HasOne<CarrierOilDetail, $this> */
    public function carrierOilDetail(): HasOne
    {
        return $this->hasOne(CarrierOilDetail::class);
    }

    /**
     * Search over name, brand, barcode, botanical names and effect tags.
     * All values are bound as parameters (no SQL injection).
     *
     * @param  Builder<Product>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('brand', 'like', $like)
                ->orWhere('barcode', 'like', $like)
                ->orWhereHas('essentialOilDetail', fn (Builder $d) => $d
                    ->where('latin_name', 'like', $like)
                    ->orWhere('effects_mental', 'like', $like)
                    ->orWhere('effects_physical', 'like', $like))
                ->orWhereHas('carrierOilDetail', fn (Builder $d) => $d
                    ->where('latin_name', 'like', $like));
        });
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeInCategory(Builder $query, ?string $category): void
    {
        if ($category !== null && ProductCategory::tryFrom($category) !== null) {
            $query->where('category', $category);
        }
    }

    /**
     * Products with less than 20 % of their initial quantity left.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->where('initial_quantity', '>', 0)
            ->whereRaw('quantity < initial_quantity * 0.2');
    }

    /**
     * Fill level in percent (0–100), derived from quantity / initial_quantity.
     */
    protected function fillPercent(): Attribute
    {
        return Attribute::get(function (): int {
            $initial = (float) $this->initial_quantity;

            if ($initial <= 0) {
                return 0;
            }

            return (int) round(min(1, max(0, (float) $this->quantity / $initial)) * 100);
        });
    }

    protected function stockStatus(): Attribute
    {
        return Attribute::get(fn (): StockStatus => StockStatus::fromRatio($this->fill_percent / 100));
    }

    /**
     * Two-letter abbreviation for the category tile, e.g. "La" for Lavendel, "VE" for Vitamin E.
     */
    protected function initials(): Attribute
    {
        return Attribute::get(function (): string {
            $words = preg_split('/\s+/u', trim($this->name)) ?: [];

            if (count($words) >= 2) {
                return Str::upper(Str::substr($words[0], 0, 1)).Str::upper(Str::substr($words[1], 0, 1));
            }

            return Str::ucfirst(Str::lower(Str::substr($this->name, 0, 2)));
        });
    }

    /**
     * Quantity without trailing zeros, e.g. "12 ml" instead of "12.00 ml".
     */
    protected function formattedQuantity(): Attribute
    {
        return Attribute::get(fn (): string => self::formatNumber($this->quantity).' '.$this->unit);
    }

    protected function formattedInitialQuantity(): Attribute
    {
        return Attribute::get(fn (): string => self::formatNumber($this->initial_quantity).' '.$this->unit);
    }

    public static function formatNumber(float|string|null $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    }

    /**
     * Add one package (the initial quantity) to the stock, e.g. after scanning a new bottle.
     */
    public function restock(): void
    {
        $this->quantity = (float) $this->quantity + (float) $this->initial_quantity;
        $this->save();
    }
}
