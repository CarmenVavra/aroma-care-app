<?php

namespace App\Livewire;

use App\Actions\SaveProduct;
use App\Enums\ProductCategory;
use App\Livewire\Forms\ProductForm;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Bestand')]
class ProductManager extends Component
{
    use WithPagination;

    /** Free-text search (name, brand, barcode, botanical name, effect tags). */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** '' = all, otherwise a ProductCategory value (essential_oil, carrier_oil, wax, additive). */
    #[Url(as: 'kategorie', except: '')]
    public string $selectedCategory = '';

    /** Only show products with less than 20 % left. */
    #[Url(as: 'fast-leer', except: false)]
    public bool $lowStockOnly = false;

    /** Create / edit modal. */
    public bool $isModalOpen = false;

    /** Form object for create / edit (incl. category specific detail fields). */
    public ProductForm $currentProduct;

    /** Product shown in the detail sheet / panel. */
    public ?int $selectedProductId = null;

    /** Set when the detail view was opened by a barcode scan ("Produkt erkannt"). */
    public bool $openedByScan = false;

    /**
     * Deep links from other pages: /?scan=1 opens the scanner, /?neu=1 the create form.
     */
    public function mount(): void
    {
        if (request()->boolean('neu')) {
            $this->createProduct();
        } elseif (request()->boolean('scan')) {
            $this->dispatch('open-scanner');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCategory(): void
    {
        if ($this->selectedCategory !== '' && ProductCategory::tryFrom($this->selectedCategory) === null) {
            $this->selectedCategory = '';
        }

        $this->resetPage();
    }

    public function updatedLowStockOnly(): void
    {
        $this->resetPage();
    }

    // ------------------------------------------------------------------
    // Computed properties
    // ------------------------------------------------------------------

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return $this->userProducts()
            ->with(['essentialOilDetail', 'carrierOilDetail'])
            ->search($this->search)
            ->inCategory($this->selectedCategory ?: null)
            ->when($this->lowStockOnly, fn ($query) => $query->lowStock())
            ->orderBy('name')
            ->paginate(config('aromacare.per_page'));
    }

    /**
     * Product counts per category for the filter chips.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function categoryCounts(): array
    {
        $counts = $this->userProducts()
            ->selectRaw('category, count(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->map(fn ($count) => (int) $count)
            ->all();

        return ['' => array_sum($counts)] + $counts;
    }

    #[Computed]
    public function lowStockCount(): int
    {
        return $this->userProducts()->lowStock()->count();
    }

    #[Computed]
    public function selectedProduct(): ?Product
    {
        if ($this->selectedProductId === null) {
            return null;
        }

        return $this->userProducts()->with(['essentialOilDetail', 'carrierOilDetail'])->find($this->selectedProductId);
    }

    // ------------------------------------------------------------------
    // Actions
    // ------------------------------------------------------------------

    /**
     * Open an empty create form, optionally with a prefilled barcode.
     */
    public function createProduct(?string $barcode = null): void
    {
        $this->currentProduct->resetForm($barcode);

        if ($this->selectedCategory !== '') {
            $this->currentProduct->category = $this->selectedCategory;
        }

        $this->closeDetail();
        $this->isModalOpen = true;
    }

    public function editProduct(Product $product): void
    {
        $this->authorize('update', $product);

        $product->load(['essentialOilDetail', 'carrierOilDetail']);

        $this->currentProduct->setProduct($product);
        $this->closeDetail();
        $this->isModalOpen = true;
    }

    public function saveProduct(SaveProduct $saveProduct): void
    {
        $validated = $this->currentProduct->validate();

        $product = $this->currentProduct->id
            ? $this->userProducts()->findOrFail($this->currentProduct->id)
            : null;

        $isNew = $product === null;
        $product = $saveProduct->handle($validated, Auth::user(), $product);

        $this->closeModal();
        $this->showProduct($product->id);
        $this->refreshLists();

        $this->dispatch('notify', message: $isNew
            ? "„{$product->name}“ wurde angelegt."
            : "„{$product->name}“ wurde gespeichert.");
    }

    /**
     * Soft delete (Product uses SoftDeletes).
     */
    public function deleteProduct(int $id): void
    {
        $product = $this->userProducts()->findOrFail($id);
        $product->delete();

        if ($this->selectedProductId === $id) {
            $this->closeDetail();
        }

        $this->refreshLists();
        $this->dispatch('notify', message: "„{$product->name}“ wurde gelöscht.");
    }

    /**
     * Add one package to the stock (e.g. "Menge erhöhen · +15 ml" after a scan).
     */
    public function restockProduct(int $id): void
    {
        $product = $this->userProducts()->findOrFail($id);
        $product->restock();

        $this->refreshLists();
        $this->dispatch('notify', message: "Bestand von „{$product->name}“ erhöht: {$product->formatted_quantity}.");
    }

    public function showProduct(int $id): void
    {
        $this->selectedProductId = $id;
        $this->openedByScan = false;
        unset($this->selectedProduct);
    }

    public function closeDetail(): void
    {
        $this->selectedProductId = null;
        $this->openedByScan = false;
        unset($this->selectedProduct);
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->currentProduct->resetForm();
    }

    public function addTag(string $field, string $value): void
    {
        $this->currentProduct->addTag($field, $value);
    }

    public function removeTag(string $field, int $index): void
    {
        $this->currentProduct->removeTag($field, $index);
    }

    /**
     * Called from the Alpine.js barcode scanner.
     *
     * Hit:    open the detail view of the existing product.
     * No hit: open the create form with the barcode prefilled.
     */
    public function handleScannedBarcode(string $code): void
    {
        $code = trim($code);

        if ($code === '' || mb_strlen($code) > 64 || ! preg_match('/^[A-Za-z0-9\-\.\/: ]+$/', $code)) {
            $this->dispatch('notify', message: 'Der gescannte Code ist ungültig.', type: 'error');

            return;
        }

        $product = $this->userProducts()->where('barcode', $code)->first();

        if ($product !== null) {
            $this->isModalOpen = false;
            $this->showProduct($product->id);
            $this->openedByScan = true;

            return;
        }

        // Scanned from within the open form: keep the entered data, only fill the barcode.
        if ($this->isModalOpen) {
            $this->currentProduct->barcode = $code;
            $this->resetValidation('currentProduct.barcode');
            $this->dispatch('notify', message: 'Barcode übernommen.');

            return;
        }

        $this->createProduct($code);
        $this->dispatch('notify', message: 'Neuer Barcode – bitte Produkt erfassen.');
    }

    public function render(): View
    {
        $categories = ProductCategory::cases();
        $units = config('aromacare.units');
        $usageTypes = config('aromacare.usage_types');
        $useAreas = config('aromacare.use_areas');
        $greeting = match (true) {
            now()->hour < 11 => 'Guten Morgen',
            now()->hour < 18 => 'Guten Tag',
            default => 'Guten Abend',
        };

        return view('livewire.product-manager', compact('categories', 'units', 'usageTypes', 'useAreas', 'greeting'));
    }

    /**
     * All queries go through the logged-in user, so nobody sees or changes foreign products.
     *
     * @return HasMany<Product, User>
     */
    private function userProducts(): HasMany
    {
        return Auth::user()->products();
    }

    private function refreshLists(): void
    {
        unset($this->products, $this->categoryCounts, $this->lowStockCount, $this->selectedProduct);
    }
}
