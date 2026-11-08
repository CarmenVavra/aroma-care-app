<?php

namespace App\Services;

use App\Enums\ProductCategory;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Compares recipe ingredients with a user's stock.
 *
 * An ingredient matches a product when one of its search terms occurs in the
 * product name or botanical name. Essential oils only match essential oils (so
 * "Rosmarin" does not match a rosemary extract additive); other ingredients
 * match any non-essential-oil product. Without search terms ("Basisöl nach
 * Wahl") every product of the ingredient's category matches.
 */
class RecipeStockMatcher
{
    /** @var Collection<int, Product> */
    private Collection $products;

    /**
     * @param  Collection<int, Product>  $products  with essentialOilDetail and carrierOilDetail loaded
     */
    public function __construct(Collection $products)
    {
        $this->products = $products;
    }

    public function matches(RecipeIngredient $ingredient, Product $product): bool
    {
        $wantsEssential = $ingredient->category === ProductCategory::EssentialOil;
        $isEssential = $product->category === ProductCategory::EssentialOil;

        if ($wantsEssential !== $isEssential) {
            return false;
        }

        $terms = $ingredient->match_terms ?? [];

        if ($terms === []) {
            return $product->category === $ingredient->category;
        }

        $haystack = Str::lower(implode(' ', array_filter([
            $product->name,
            $product->essentialOilDetail?->latin_name,
            $product->carrierOilDetail?->latin_name,
        ])));

        foreach ($terms as $term) {
            if (str_contains($haystack, Str::lower($term))) {
                return true;
            }
        }

        return false;
    }

    /**
     * First matching product that still has something left.
     */
    public function stockFor(RecipeIngredient $ingredient): ?Product
    {
        return $this->products->first(
            fn (Product $product) => (float) $product->quantity > 0 && $this->matches($ingredient, $product)
        );
    }

    /**
     * Per ingredient id the product in stock (or null), plus the missing required ingredients.
     *
     * @return array{stock: array<int, Product|null>, missing: list<string>, complete: bool}
     */
    public function availability(Recipe $recipe): array
    {
        $stock = [];
        $missing = [];

        foreach ($recipe->ingredients as $ingredient) {
            $stock[$ingredient->id] = $this->stockFor($ingredient);

            if ($stock[$ingredient->id] === null && ! $ingredient->optional) {
                $missing[] = $ingredient->name;
            }
        }

        return ['stock' => $stock, 'missing' => $missing, 'complete' => $missing === []];
    }

    /**
     * Recipes that use the given product (regardless of its current quantity).
     *
     * @param  Collection<int, Recipe>  $recipes  with ingredients loaded
     * @return Collection<int, Recipe>
     */
    public function recipesUsing(Product $product, Collection $recipes): Collection
    {
        return $recipes->filter(
            fn (Recipe $recipe) => $recipe->ingredients->contains(fn (RecipeIngredient $i) => $this->matches($i, $product))
        )->values();
    }
}
