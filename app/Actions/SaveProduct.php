<?php

namespace App\Actions;

use App\Enums\ProductCategory;
use App\Livewire\Forms\ProductForm;
use App\Models\CatalogEntry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a product together with its category specific detail
 * record (EssentialOilDetail / CarrierOilDetail) in one transaction.
 */
class SaveProduct
{
    /**
     * @param  array<string, mixed>  $validated  Data validated by ProductForm
     */
    public function handle(array $validated, User $user, ?Product $product = null): Product
    {
        return DB::transaction(function () use ($validated, $user, $product) {
            $attributes = ProductForm::productAttributes($validated);
            $category = ProductCategory::from($attributes['category']);

            if ($product === null) {
                // A new package starts full: initial quantity = current quantity.
                $product = $user->products()->create($attributes + ['initial_quantity' => $attributes['quantity']]);
            } else {
                $product->fill($attributes);

                // Keep the fill ratio meaningful if stock was raised above the package size.
                if ((float) $product->quantity > (float) $product->initial_quantity) {
                    $product->initial_quantity = $product->quantity;
                }

                $product->save();
            }

            $this->syncDetails($product, $category, $validated);
            $product->load(['essentialOilDetail', 'carrierOilDetail']);

            // Shared code catalogue: the next person scanning this code gets the data prefilled.
            CatalogEntry::learnFrom($product);

            return $product;
        });
    }

    /**
     * Upsert the detail record that matches the category and remove the
     * one that no longer applies (e.g. after switching the category).
     *
     * @param  array<string, mixed>  $validated
     */
    private function syncDetails(Product $product, ProductCategory $category, array $validated): void
    {
        $latinName = trim((string) ($validated['latin_name'] ?? '')) ?: null;

        if ($category === ProductCategory::EssentialOil) {
            $product->essentialOilDetail()->updateOrCreate([], [
                'latin_name' => $latinName,
                'effects_mental' => array_values($validated['effects_mental'] ?? []),
                'effects_physical' => array_values($validated['effects_physical'] ?? []),
                'usage_types' => array_values($validated['usage_types'] ?? []),
                'dilution_hint' => trim((string) ($validated['dilution_hint'] ?? '')) ?: null,
            ]);
        } else {
            $product->essentialOilDetail()->delete();
        }

        if ($category === ProductCategory::CarrierOil) {
            $product->carrierOilDetail()->updateOrCreate([], [
                'latin_name' => $latinName,
                'certification' => trim((string) ($validated['certification'] ?? '')) ?: null,
                'comedogenic_rating' => $validated['comedogenic_rating'] ?? null,
                'absorption' => ($validated['absorption'] ?? '') ?: null,
                'use_areas' => array_values($validated['use_areas'] ?? []),
            ]);
        } else {
            $product->carrierOilDetail()->delete();
        }
    }
}
