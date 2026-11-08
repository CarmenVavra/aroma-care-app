<?php

namespace App\Services\ProductLookup;

use App\Enums\ProductCategory;

/**
 * Product data found for a barcode. Every field is optional: the form only
 * fills what is known and the user checks everything before saving.
 */
final readonly class ProductSuggestion
{
    public function __construct(
        public string $source,
        public ?string $name = null,
        public ?string $brand = null,
        public ?ProductCategory $category = null,
        public ?float $quantity = null,
        public ?string $unit = null,
        public ?string $latinName = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'name' => $this->name,
            'brand' => $this->brand,
            'category' => $this->category?->value,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'latinName' => $this->latinName,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            source: (string) $data['source'],
            name: $data['name'] ?? null,
            brand: $data['brand'] ?? null,
            category: isset($data['category']) ? ProductCategory::tryFrom($data['category']) : null,
            quantity: isset($data['quantity']) ? (float) $data['quantity'] : null,
            unit: $data['unit'] ?? null,
            latinName: $data['latinName'] ?? null,
        );
    }

    public function hasName(): bool
    {
        return $this->name !== null && $this->name !== '';
    }
}
