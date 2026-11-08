<?php

namespace App\Models;

use App\Enums\ProductCategory;
use App\Enums\RecipeCategory;
use App\Enums\RecipeMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Starter recipe from the shared catalogue (database/data/recipes.php).
 *
 * @property RecipeCategory $category
 * @property RecipeMethod $method
 * @property array<int, string> $steps
 * @property-read float|null $dilution_percent
 */
class Recipe extends Model
{
    /** Average volume of one drop of essential oil in ml (20 drops ≈ 1 ml). */
    public const DROP_ML = 0.05;

    protected $fillable = [
        'slug',
        'title',
        'purpose',
        'category',
        'method',
        'yield',
        'steps',
        'safety_note',
    ];

    protected function casts(): array
    {
        return [
            'category' => RecipeCategory::class,
            'method' => RecipeMethod::class,
            'steps' => 'array',
        ];
    }

    /** @return HasMany<RecipeIngredient, $this> */
    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('position');
    }

    /**
     * Search over title, purpose and ingredient names (bound parameters only).
     *
     * @param  Builder<Recipe>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        $query->where(function (Builder $query) use ($like) {
            $query->where('title', 'like', $like)
                ->orWhere('purpose', 'like', $like)
                ->orWhereHas('ingredients', fn (Builder $q) => $q->where('name', 'like', $like));
        });
    }

    /**
     * Share of essential oil in a skin product in percent, e.g. 1.5.
     * Null for diffuser, bath and inhalation, where it has no meaning.
     */
    protected function dilutionPercent(): Attribute
    {
        return Attribute::get(function (): ?float {
            if (! $this->method->isTopical()) {
                return null;
            }

            $oil = 0.0;
            $base = 0.0;

            foreach ($this->ingredients as $ingredient) {
                if ($ingredient->category === ProductCategory::EssentialOil) {
                    $oil += (float) $ingredient->amount * self::DROP_ML;
                } elseif (in_array($ingredient->unit, ['ml', 'g'], true)) {
                    $base += (float) $ingredient->amount;
                }
            }

            return $base > 0 && $oil > 0 ? round($oil / $base * 100, 1) : null;
        });
    }
}
