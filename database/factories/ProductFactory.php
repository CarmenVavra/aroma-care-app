<?php

namespace Database\Factories;

use App\Enums\Absorption;
use App\Enums\ProductCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $initial = $this->faker->randomElement([10, 15, 30, 100, 250]);

        return [
            'name' => $this->faker->unique()->word(),
            'brand' => $this->faker->company(),
            'barcode' => $this->faker->unique()->ean13(),
            'category' => ProductCategory::Additive,
            'user_id' => User::factory(),
            'quantity' => $this->faker->numberBetween(1, $initial),
            'initial_quantity' => $initial,
            'unit' => 'ml',
            'expires_at' => now()->addYears(2)->endOfMonth(),
        ];
    }

    public function essentialOil(): static
    {
        return $this->state(['category' => ProductCategory::EssentialOil])
            ->afterCreating(fn (Product $product) => $product->essentialOilDetail()->create([
                'latin_name' => 'Lavandula angustifolia',
                'effects_mental' => ['Beruhigend', 'Ausgleichend'],
                'effects_physical' => ['Entspannend'],
                'usage_types' => ['Verdampfen', 'Hautpflege'],
                'dilution_hint' => '1–2 %',
            ]));
    }

    public function carrierOil(): static
    {
        return $this->state(['category' => ProductCategory::CarrierOil])
            ->afterCreating(fn (Product $product) => $product->carrierOilDetail()->create([
                'latin_name' => 'Simmondsia chinensis',
                'certification' => 'Bio · kaltgepresst',
                'comedogenic_rating' => 2,
                'absorption' => Absorption::Medium,
                'use_areas' => ['Gesicht', 'Massage'],
            ]));
    }
}
