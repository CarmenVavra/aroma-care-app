<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Livewire\ProductManager;
use App\Livewire\RecipeFinder;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\RecipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecipeFinderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RecipeSeeder::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function stock(string $name, ProductCategory $category, float $quantity = 10, ?User $owner = null): Product
    {
        return Product::factory()->for($owner ?? $this->user)->create([
            'name' => $name,
            'category' => $category,
            'quantity' => $quantity,
            'initial_quantity' => 15,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        auth()->logout();

        $this->get(route('recipes'))->assertRedirect(route('login'));
    }

    public function test_page_lists_starter_recipes_with_safety_notice(): void
    {
        $count = count(require database_path('data/recipes.php'));

        $this->get(route('recipes'))
            ->assertOk()
            ->assertSee('Rezept-Finder')
            ->assertSee('Vor der Anwendung lesen.')
            ->assertSee('Schlaf-Roll-on')
            ->assertSee("0 von {$count} Rezepten mit deinem Bestand machbar");
    }

    public function test_seeder_is_idempotent_and_removes_dropped_recipes(): void
    {
        $count = Recipe::count();
        $ingredients = Recipe::withCount('ingredients')->get()->sum('ingredients_count');
        Recipe::create([
            'slug' => 'veraltet', 'title' => 'Alt', 'purpose' => 'x', 'category' => 'room',
            'method' => 'diffuser', 'yield' => '1', 'steps' => ['x'],
        ]);

        $this->seed(RecipeSeeder::class);

        $this->assertSame($count, Recipe::count());
        $this->assertSame($ingredients, Recipe::withCount('ingredients')->get()->sum('ingredients_count'));
        $this->assertDatabaseMissing('recipes', ['slug' => 'veraltet']);
    }

    public function test_dilution_is_calculated_for_skin_products_only(): void
    {
        $rollOn = Recipe::with('ingredients')->where('slug', 'schlaf-roll-on')->firstOrFail();
        $diffuser = Recipe::with('ingredients')->where('slug', 'abendruhe-diffuser')->firstOrFail();

        // 3 drops × 0.05 ml in 10 ml jojoba
        $this->assertSame(1.5, $rollOn->dilution_percent);
        $this->assertNull($diffuser->dilution_percent);
    }

    public function test_starter_recipes_stay_within_safe_dilution(): void
    {
        foreach (Recipe::with('ingredients')->get() as $recipe) {
            if ($recipe->dilution_percent !== null) {
                $this->assertLessThanOrEqual(2.0, $recipe->dilution_percent, $recipe->slug);
            }
        }
    }

    public function test_recipe_is_complete_when_all_ingredients_are_in_stock(): void
    {
        $this->stock('Lavendel', ProductCategory::EssentialOil);
        $this->stock('Zedernholz Atlas', ProductCategory::EssentialOil);
        $this->stock('Jojobaöl Bio', ProductCategory::CarrierOil);

        $component = Livewire::test(RecipeFinder::class);
        $rollOn = Recipe::where('slug', 'schlaf-roll-on')->firstOrFail();
        $diffuser = Recipe::where('slug', 'abendruhe-diffuser')->firstOrFail();

        $this->assertTrue($component->get('availability')[$rollOn->id]['complete']);
        $this->assertSame(['Orange süß'], $component->get('availability')[$diffuser->id]['missing']);
        $component->assertSee('Alles vorrätig')->assertSee('Fehlt: Orange süß');
    }

    public function test_matching_uses_botanical_name(): void
    {
        $product = $this->stock('Wild Orange', ProductCategory::EssentialOil);
        $product->essentialOilDetail()->create(['latin_name' => 'Citrus sinensis']);
        $this->stock('Lavendel', ProductCategory::EssentialOil);

        $diffuser = Recipe::where('slug', 'abendruhe-diffuser')->firstOrFail();

        $this->assertTrue(Livewire::test(RecipeFinder::class)->get('availability')[$diffuser->id]['complete']);
    }

    public function test_empty_products_and_other_users_stock_do_not_count(): void
    {
        $this->stock('Lavendel', ProductCategory::EssentialOil, quantity: 0);
        $this->stock('Orange süß', ProductCategory::EssentialOil, owner: User::factory()->create());

        $diffuser = Recipe::where('slug', 'abendruhe-diffuser')->firstOrFail();

        $this->assertSame(['Lavendel', 'Orange süß'], Livewire::test(RecipeFinder::class)->get('availability')[$diffuser->id]['missing']);
    }

    public function test_essential_oil_ingredient_does_not_match_an_additive(): void
    {
        $this->stock('Rosmarinextrakt', ProductCategory::Additive);
        $this->stock('Zitrone', ProductCategory::EssentialOil);

        $focus = Recipe::where('slug', 'fokus-diffuser')->firstOrFail();

        $this->assertSame(['Rosmarin'], Livewire::test(RecipeFinder::class)->get('availability')[$focus->id]['missing']);
    }

    public function test_any_carrier_oil_fills_a_wildcard_ingredient(): void
    {
        $this->stock('Aprikosenkernöl', ProductCategory::CarrierOil);
        $this->stock('Lavendel', ProductCategory::EssentialOil);
        $this->stock('Geranie', ProductCategory::EssentialOil);

        $bodyOil = Recipe::where('slug', 'koerperoel')->firstOrFail();

        $this->assertTrue(Livewire::test(RecipeFinder::class)->get('availability')[$bodyOil->id]['complete']);
    }

    public function test_optional_ingredients_are_not_required(): void
    {
        $this->stock('Süßmandelöl', ProductCategory::CarrierOil);
        $this->stock('Sheabutter', ProductCategory::CarrierOil);
        $this->stock('Bienenwachs', ProductCategory::Wax);

        $lipBalm = Recipe::where('slug', 'lippenbalsam')->firstOrFail();

        $this->assertTrue(Livewire::test(RecipeFinder::class)->get('availability')[$lipBalm->id]['complete']);
    }

    public function test_filters_by_makeable_category_and_search(): void
    {
        $this->stock('Lavendel', ProductCategory::EssentialOil);
        $this->stock('Orange süß', ProductCategory::EssentialOil);

        Livewire::test(RecipeFinder::class)
            ->set('onlyMakeable', true)
            ->assertSee('Abendruhe')
            ->assertDontSee('Schlaf-Roll-on')
            ->set('onlyMakeable', false)
            ->set('selectedCategory', 'hair')
            ->assertSee('Kopfhaut-Öl')
            ->assertDontSee('Abendruhe')
            ->set('selectedCategory', 'unbekannt')
            ->assertSet('selectedCategory', '')
            ->set('search', 'Teebaum')
            ->assertSee('Pickel-Punktpflege')
            ->assertDontSee('Abendruhe');
    }

    public function test_makeable_recipes_are_listed_first(): void
    {
        $this->stock('Zitrone', ProductCategory::EssentialOil);
        $this->stock('Orange süß', ProductCategory::EssentialOil);

        $titles = Livewire::test(RecipeFinder::class)->get('recipes')->pluck('title');

        $this->assertSame('Zitrusfrische', $titles->first());
    }

    public function test_product_filter_only_accepts_own_products(): void
    {
        $own = $this->stock('Teebaum', ProductCategory::EssentialOil);
        $foreign = $this->stock('Teebaum', ProductCategory::EssentialOil, owner: User::factory()->create());

        Livewire::withQueryParams(['produkt' => $own->id])->test(RecipeFinder::class)
            ->assertSee('Mit Teebaum')
            ->assertSee('Pickel-Punktpflege')
            ->assertDontSee('Abendruhe')
            ->call('clearProduct')
            ->assertSee('Abendruhe');

        Livewire::withQueryParams(['produkt' => $foreign->id])->test(RecipeFinder::class)
            ->assertDontSee('Mit Teebaum')
            ->assertSee('Abendruhe');
    }

    public function test_product_detail_shows_recipe_ideas(): void
    {
        $tea = $this->stock('Teebaum', ProductCategory::EssentialOil);

        Livewire::test(ProductManager::class)
            ->call('showProduct', $tea->id)
            ->assertSee('Rezeptideen')
            ->assertSee('Pickel-Punktpflege')
            ->assertSee(route('recipes', ['produkt' => $tea->id]));
    }

    public function test_navigation_links_to_recipe_finder(): void
    {
        $this->get(route('stock'))->assertSee(route('recipes'))->assertDontSee('Demnächst verfügbar');
    }
}
