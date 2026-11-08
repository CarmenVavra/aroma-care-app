<?php

namespace Database\Seeders;

use App\Enums\Absorption;
use App\Enums\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Example data from the design handoff. Values (effects, comedogenic ratings, ...)
 * are sample data and must be checked by a domain expert.
 */
class ProductSeeder extends Seeder
{
    public function run(User $user): void
    {
        DB::transaction(function () use ($user) {
            $essential = [
                ['Lavendel', 'Lavandula angustifolia', 12, '4260123456785', ['Beruhigend', 'Ausgleichend', 'Schlaffördernd'], ['Entspannend', 'Hautberuhigend'], ['Verdampfen', 'Hautpflege', 'Badezusatz']],
                ['Pfefferminze', 'Mentha piperita', 4, '4260123456792', ['Belebend', 'Konzentrationsfördernd'], ['Kühlend'], ['Verdampfen', 'Inhalieren']],
                ['Teebaum', 'Melaleuca alternifolia', 2, '4260123456808', ['Klärend'], ['Antibakteriell', 'Hautklärend'], ['Hautpflege']],
                ['Zitrone', 'Citrus limon', 8, '4260123456815', ['Stimmungsaufhellend'], ['Erfrischend'], ['Verdampfen']],
            ];

            foreach ($essential as [$name, $latin, $qty, $ean, $mental, $physical, $usage]) {
                $product = $user->products()->create([
                    'name' => $name,
                    'brand' => 'doTERRA',
                    'barcode' => $ean,
                    'category' => ProductCategory::EssentialOil,
                    'quantity' => $qty,
                    'initial_quantity' => 15,
                    'unit' => 'ml',
                    'expires_at' => '2029-03-31',
                ]);

                $product->essentialOilDetail()->create([
                    'latin_name' => $latin,
                    'effects_mental' => $mental,
                    'effects_physical' => $physical,
                    'usage_types' => $usage,
                    'dilution_hint' => '1–2 %',
                ]);
            }

            $carrier = [
                ['Jojobaöl', 'Simmondsia chinensis', 'Bio · kaltgepresst', 85, 100, 2, Absorption::Medium, ['Gesicht', 'Massage']],
                ['Süßmandelöl', 'Prunus dulcis', null, 100, 250, 2, Absorption::Slow, ['Massage', 'Körper']],
            ];

            foreach ($carrier as [$name, $latin, $cert, $qty, $initial, $rating, $absorption, $areas]) {
                $product = $user->products()->create([
                    'name' => $name,
                    'brand' => 'Naturkosmetik-Händler',
                    'category' => ProductCategory::CarrierOil,
                    'quantity' => $qty,
                    'initial_quantity' => $initial,
                    'unit' => 'ml',
                    'expires_at' => '2027-11-30',
                ]);

                $product->carrierOilDetail()->create([
                    'latin_name' => $latin,
                    'certification' => $cert,
                    'comedogenic_rating' => $rating,
                    'absorption' => $absorption,
                    'use_areas' => $areas,
                ]);
            }

            $user->products()->create([
                'name' => 'Bienenwachs',
                'brand' => 'Pastillen',
                'category' => ProductCategory::Wax,
                'quantity' => 155,
                'initial_quantity' => 250,
                'unit' => 'g',
            ]);

            $user->products()->create([
                'name' => 'Vitamin E',
                'brand' => 'Tocopherol',
                'category' => ProductCategory::Additive,
                'quantity' => 8,
                'initial_quantity' => 20,
                'unit' => 'ml',
            ]);
        });
    }
}
