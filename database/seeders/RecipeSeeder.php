<?php

namespace Database\Seeders;

use App\Models\Recipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Syncs the starter recipes from database/data/recipes.php.
 * Idempotent (matched by slug), so deploy.sh runs it on every release.
 */
class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<int, array<string, mixed>> $recipes */
        $recipes = require database_path('data/recipes.php');

        DB::transaction(function () use ($recipes) {
            foreach ($recipes as $data) {
                $recipe = Recipe::updateOrCreate(['slug' => $data['slug']], [
                    'title' => $data['title'],
                    'purpose' => $data['purpose'],
                    'category' => $data['category'],
                    'method' => $data['method'],
                    'yield' => $data['yield'],
                    'steps' => $data['steps'],
                    'safety_note' => $data['safety'] ?? null,
                ]);

                $recipe->ingredients()->delete();

                foreach ($data['ingredients'] as $position => $ingredient) {
                    [$name, $category, $amount, $unit, $terms] = $ingredient;

                    $recipe->ingredients()->create([
                        'position' => $position,
                        'name' => $name,
                        'category' => $category,
                        'amount' => $amount,
                        'unit' => $unit,
                        'match_terms' => $terms,
                        'optional' => $ingredient[5] ?? false,
                    ]);
                }
            }

            // Recipes removed from the data file disappear from the finder as well.
            Recipe::whereNotIn('slug', array_column($recipes, 'slug'))->delete();
        });
    }
}
