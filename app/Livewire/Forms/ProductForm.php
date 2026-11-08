<?php

namespace App\Livewire\Forms;

use App\Enums\Absorption;
use App\Enums\ProductCategory;
use App\Models\Product;
use App\Services\ProductLookup\ProductSuggestion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Livewire form object for creating / editing a product.
 *
 * Acts as the Livewire counterpart of a Form Request: it owns the
 * validation rules, messages and the mapping from/to the Eloquent model.
 */
class ProductForm extends Form
{
    public ?int $id = null;

    // ---- Base fields --------------------------------------------------
    public string $category = 'essential_oil';

    public string $name = '';

    public string $brand = '';

    public string $barcode = '';

    public string $quantity = '';

    public string $unit = 'ml';

    /** Format MM/JJJJ, e.g. "03/2029". */
    public string $expires_at = '';

    // ---- Essential oil -----------------------------------------------
    public string $latin_name = '';

    /** @var list<string> */
    public array $effects_mental = [];

    /** @var list<string> */
    public array $effects_physical = [];

    /** @var list<string> */
    public array $usage_types = [];

    public string $dilution_hint = '';

    // ---- Carrier oil --------------------------------------------------
    public string $certification = '';

    public ?int $comedogenic_rating = null;

    public string $absorption = '';

    /** @var list<string> */
    public array $use_areas = [];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isEssential = $this->category === ProductCategory::EssentialOil->value;
        $isCarrier = $this->category === ProductCategory::CarrierOil->value;

        return [
            'category' => ['required', Rule::enum(ProductCategory::class)],
            'name' => ['required', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'barcode' => [
                'nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-\.\/: ]+$/',
                Rule::unique('products', 'barcode')
                    ->ignore($this->id)
                    ->where('user_id', Auth::id())
                    ->whereNull('deleted_at'),
            ],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'unit' => ['required', Rule::in(config('aromacare.units'))],
            'expires_at' => ['nullable', 'regex:/^(0[1-9]|1[0-2])\/\d{4}$/'],

            'latin_name' => ['nullable', 'string', 'max:160'],

            'effects_mental' => [Rule::excludeIf(! $isEssential), 'array', 'max:20'],
            'effects_mental.*' => ['string', 'max:40'],
            'effects_physical' => [Rule::excludeIf(! $isEssential), 'array', 'max:20'],
            'effects_physical.*' => ['string', 'max:40'],
            'usage_types' => [Rule::excludeIf(! $isEssential), 'array'],
            'usage_types.*' => [Rule::in(config('aromacare.usage_types'))],
            'dilution_hint' => [Rule::excludeIf(! $isEssential), 'nullable', 'string', 'max:40'],

            'certification' => [Rule::excludeIf(! $isCarrier), 'nullable', 'string', 'max:80'],
            'comedogenic_rating' => [Rule::excludeIf(! $isCarrier), 'nullable', 'integer', 'between:0,5'],
            'absorption' => [Rule::excludeIf(! $isCarrier), 'nullable', Rule::enum(Absorption::class)],
            'use_areas' => [Rule::excludeIf(! $isCarrier), 'array'],
            'use_areas.*' => [Rule::in(config('aromacare.use_areas'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bitte gib einen Namen ein.',
            'quantity.required' => 'Bitte gib eine Menge ein.',
            'quantity.numeric' => 'Die Menge muss eine Zahl sein.',
            'quantity.gt' => 'Die Menge muss größer als 0 sein.',
            'expires_at.regex' => 'Bitte im Format MM/JJJJ angeben, z. B. 03/2029.',
            'barcode.unique' => 'Dieser Barcode ist bereits einem Produkt zugeordnet.',
            'barcode.regex' => 'Der Barcode enthält ungültige Zeichen.',
            'comedogenic_rating.between' => 'Die Komedogenität muss zwischen 0 und 5 liegen.',
        ];
    }

    /**
     * Fill the form from an existing product (edit mode).
     */
    public function setProduct(Product $product): void
    {
        $this->resetForm();

        $this->id = $product->id;
        $this->category = $product->category->value;
        $this->name = $product->name;
        $this->brand = (string) $product->brand;
        $this->barcode = (string) $product->barcode;
        // decimal:2 cast yields e.g. "12.50" -> show "12.5"
        $this->quantity = rtrim(rtrim((string) $product->quantity, '0'), '.');
        $this->unit = $product->unit;
        $this->expires_at = $product->expires_at?->format('m/Y') ?? '';

        if ($detail = $product->essentialOilDetail) {
            $this->latin_name = (string) $detail->latin_name;
            $this->effects_mental = $detail->effects_mental ?? [];
            $this->effects_physical = $detail->effects_physical ?? [];
            $this->usage_types = $detail->usage_types ?? [];
            $this->dilution_hint = (string) $detail->dilution_hint;
        }

        if ($detail = $product->carrierOilDetail) {
            $this->latin_name = (string) $detail->latin_name;
            $this->certification = (string) $detail->certification;
            $this->comedogenic_rating = $detail->comedogenic_rating;
            $this->absorption = (string) $detail->absorption?->value;
            $this->use_areas = $detail->use_areas ?? [];
        }
    }

    /**
     * New package of a product the user deleted earlier: take over all data,
     * start full again and leave the expiry date empty.
     */
    public function fillFromPrevious(Product $previous, string $barcode): void
    {
        $this->setProduct($previous);

        $this->id = null;
        $this->barcode = $barcode;
        $this->quantity = rtrim(rtrim((string) $previous->initial_quantity, '0'), '.');
        $this->expires_at = '';
    }

    /**
     * Fill empty fields from a database lookup; entered values are never overwritten.
     *
     * @return bool whether anything was filled in
     */
    public function applySuggestion(ProductSuggestion $suggestion, bool $withCategory = true): bool
    {
        $filled = false;

        if ($withCategory && $suggestion->category !== null) {
            $this->category = $suggestion->category->value;
            $filled = true;
        }

        foreach (['name' => $suggestion->name, 'brand' => $suggestion->brand, 'latin_name' => $suggestion->latinName] as $field => $value) {
            if ($this->{$field} === '' && $value !== null && $value !== '') {
                $this->{$field} = $value;
                $filled = true;
            }
        }

        if ($this->quantity === '' && $suggestion->quantity !== null && $suggestion->quantity > 0) {
            // 15.0 -> "15", 0.5 -> "0.5" (same format as in edit mode)
            $this->quantity = rtrim(rtrim(number_format($suggestion->quantity, 2, '.', ''), '0'), '.');

            if (in_array($suggestion->unit, config('aromacare.units'), true)) {
                $this->unit = (string) $suggestion->unit;
            }

            $filled = true;
        }

        return $filled;
    }

    /**
     * Reset to a blank "create" form, optionally prefilled with a barcode.
     */
    public function resetForm(?string $barcode = null): void
    {
        $this->reset();
        $this->resetErrorBag();

        if ($barcode !== null) {
            $this->barcode = $barcode;
        }
    }

    public function addTag(string $field, string $value): void
    {
        $value = trim(strip_tags($value));

        if (! in_array($field, ['effects_mental', 'effects_physical'], true) || $value === '') {
            return;
        }

        $value = mb_substr($value, 0, 40);

        if (! in_array($value, $this->{$field}, true)) {
            $this->{$field}[] = $value;
        }
    }

    public function removeTag(string $field, int $index): void
    {
        if (! in_array($field, ['effects_mental', 'effects_physical'], true)) {
            return;
        }

        unset($this->{$field}[$index]);
        $this->{$field} = array_values($this->{$field});
    }

    /**
     * Base attributes for the products table.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function productAttributes(array $validated): array
    {
        return [
            'category' => $validated['category'],
            'name' => trim($validated['name']),
            'brand' => self::nullIfEmpty($validated['brand'] ?? null),
            'barcode' => self::nullIfEmpty($validated['barcode'] ?? null),
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'expires_at' => empty($validated['expires_at'])
                ? null
                : Carbon::createFromFormat('!m/Y', $validated['expires_at'])->endOfMonth()->toDateString(),
        ];
    }

    private static function nullIfEmpty(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
