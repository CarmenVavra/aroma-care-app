<?php

namespace Tests\Feature;

use App\Livewire\ProductManager;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Users must never see or change another user's inventory.
 */
class ProductIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $anna;

    private Product $foreign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->anna = User::factory()->create();
        $this->foreign = Product::factory()->for(User::factory())->create([
            'name' => 'Fremdes Öl',
            'barcode' => '4006381333931',
        ]);
    }

    public function test_foreign_products_are_not_listed_or_counted(): void
    {
        Product::factory()->for($this->anna)->create(['name' => 'Eigenes Öl']);

        Livewire::actingAs($this->anna)->test(ProductManager::class)
            ->assertSee('Eigenes Öl')
            ->assertDontSee('Fremdes Öl')
            ->assertSet('categoryCounts', ['' => 1, 'additive' => 1]);
    }

    public function test_foreign_product_cannot_be_edited(): void
    {
        Livewire::actingAs($this->anna)->test(ProductManager::class)
            ->call('editProduct', $this->foreign->id)
            ->assertForbidden();
    }

    public function test_foreign_product_cannot_be_deleted(): void
    {
        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($this->anna)->test(ProductManager::class)
            ->call('deleteProduct', $this->foreign->id);
    }

    public function test_foreign_product_cannot_be_shown(): void
    {
        Livewire::actingAs($this->anna)->test(ProductManager::class)
            ->call('showProduct', $this->foreign->id)
            ->assertDontSee('Fremdes Öl');
    }

    public function test_scanning_a_foreign_barcode_opens_create_form(): void
    {
        Livewire::actingAs($this->anna)->test(ProductManager::class)
            ->call('handleScannedBarcode', '4006381333931')
            ->assertSet('isModalOpen', true)
            ->assertSet('selectedProductId', null);
    }

    public function test_same_barcode_may_exist_for_different_users(): void
    {
        Livewire::actingAs($this->anna)->test(ProductManager::class)
            ->call('createProduct', '4006381333931')
            ->set('currentProduct.name', 'Mein Öl')
            ->set('currentProduct.quantity', '10')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $this->assertSame(1, $this->anna->products()->count());
    }
}
