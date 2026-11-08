<?php

namespace App\Livewire;

use App\Enums\RecipeCategory;
use App\Models\Product;
use App\Models\Recipe;
use App\Services\RecipeStockMatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Collection<int, Product> $stock
 * @property-read RecipeStockMatcher $matcher
 * @property-read Product|null $filterProduct
 * @property-read array<int, array{stock: array<int, Product|null>, missing: list<string>, complete: bool}> $availability
 * @property-read Collection<int, Recipe> $recipes
 */
#[Title('Rezept-Finder')]
class RecipeFinder extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'kategorie', except: '')]
    public string $selectedCategory = '';

    #[Url(as: 'machbar', except: false)]
    public bool $onlyMakeable = false;

    #[Url(as: 'produkt', except: null)]
    public ?int $productId = null;

    public function updatedSelectedCategory(string $value): void
    {
        if (RecipeCategory::tryFrom($value) === null) {
            $this->selectedCategory = '';
        }
    }

    public function clearProduct(): void
    {
        $this->productId = null;
        unset($this->filterProduct, $this->recipes);
    }

    /**
     * The user's stock incl. botanical names (needed for matching).
     *
     * @return Collection<int, Product>
     */
    #[Computed]
    public function stock(): Collection
    {
        return Auth::user()->products()->with(['essentialOilDetail', 'carrierOilDetail'])->orderBy('name')->get();
    }

    #[Computed]
    public function matcher(): RecipeStockMatcher
    {
        return new RecipeStockMatcher($this->stock);
    }

    /**
     * Product from "?produkt=": only the user's own products are accepted.
     */
    #[Computed]
    public function filterProduct(): ?Product
    {
        return $this->productId === null ? null : $this->stock->firstWhere('id', $this->productId);
    }

    /**
     * Availability per recipe id for all recipes (used for counts and cards).
     *
     * @return array<int, array{stock: array<int, Product|null>, missing: list<string>, complete: bool}>
     */
    #[Computed]
    public function availability(): array
    {
        return $this->allRecipes()
            ->mapWithKeys(fn (Recipe $recipe) => [$recipe->id => $this->matcher->availability($recipe)])
            ->all();
    }

    /**
     * Filtered recipes: what can be made with the stock first, then by fewest missing ingredients.
     *
     * @return Collection<int, Recipe>
     */
    #[Computed]
    public function recipes(): Collection
    {
        $recipes = Recipe::query()
            ->with('ingredients')
            ->search($this->search)
            ->when(RecipeCategory::tryFrom($this->selectedCategory), fn ($query, RecipeCategory $category) => $query->where('category', $category))
            ->orderBy('title')
            ->get();

        if ($this->filterProduct !== null) {
            $recipes = $this->matcher->recipesUsing($this->filterProduct, $recipes);
        }

        $availability = $this->availability;

        return $recipes
            ->when($this->onlyMakeable, fn (Collection $items) => $items->filter(fn (Recipe $r) => $availability[$r->id]['complete']))
            ->sortBy([
                fn (Recipe $a, Recipe $b) => $availability[$b->id]['complete'] <=> $availability[$a->id]['complete'],
                fn (Recipe $a, Recipe $b) => count($availability[$a->id]['missing']) <=> count($availability[$b->id]['missing']),
                fn (Recipe $a, Recipe $b) => strcoll($a->title, $b->title),
            ])
            ->values();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function categoryCounts(): array
    {
        $counts = $this->allRecipes()->countBy(fn (Recipe $recipe) => $recipe->category->value)->all();

        return ['' => array_sum($counts)] + $counts;
    }

    #[Computed]
    public function makeableCount(): int
    {
        return count(array_filter($this->availability, fn (array $a) => $a['complete']));
    }

    public function render(): View
    {
        $categories = RecipeCategory::cases();

        return view('livewire.recipe-finder', compact('categories'));
    }

    /**
     * @return Collection<int, Recipe>
     */
    private function allRecipes(): Collection
    {
        return once(fn () => Recipe::with('ingredients')->get());
    }
}
