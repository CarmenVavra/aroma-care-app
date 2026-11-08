<?php

namespace Tests\Feature;

use App\Livewire\ProductManager;
use App\Models\CatalogEntry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Cache::flush();
        config(['aromacare.lookup.enabled' => true]);
    }

    private function saveProduct(User $user, string $code, string $name = 'Lavender'): void
    {
        Livewire::actingAs($user)->test(ProductManager::class)
            ->call('createProduct', $code)
            ->set('currentProduct.category', 'essential_oil')
            ->set('currentProduct.name', $name)
            ->set('currentProduct.brand', 'doTERRA')
            ->set('currentProduct.quantity', '15')
            ->set('currentProduct.latin_name', 'Lavandula angustifolia')
            ->call('saveProduct')
            ->assertHasNoErrors();
    }

    public function test_saving_a_product_adds_its_code_to_the_catalog_without_user_reference(): void
    {
        $this->saveProduct(User::factory()->create(), '60204650');

        $entry = CatalogEntry::where('code', '60204650')->firstOrFail();
        $this->assertSame('Lavender', $entry->name);
        $this->assertSame('doTERRA', $entry->brand);
        $this->assertSame('15.00', $entry->quantity);
        $this->assertSame('Lavandula angustifolia', $entry->latin_name);
        $this->assertArrayNotHasKey('user_id', $entry->getAttributes());
    }

    public function test_products_without_code_are_not_added(): void
    {
        $this->saveProduct(User::factory()->create(), '');

        $this->assertSame(0, CatalogEntry::count());
    }

    public function test_other_user_gets_the_data_prefilled_without_external_requests(): void
    {
        $this->saveProduct(User::factory()->create(), '60204650');

        Livewire::actingAs(User::factory()->create())->test(ProductManager::class)
            ->call('handleScannedBarcode', '60204650')
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.name', 'Lavender')
            ->assertSet('currentProduct.brand', 'doTERRA')
            ->assertSet('currentProduct.quantity', '15')
            ->assertSet('currentProduct.latin_name', 'Lavandula angustifolia')
            ->assertSet('suggestionSource', CatalogEntry::SOURCE);

        Http::assertNothingSent();
    }

    public function test_catalog_is_asked_before_public_databases(): void
    {
        $this->saveProduct(User::factory()->create(), '5055416391889', 'Golden Jojoba Oil');

        Livewire::actingAs(User::factory()->create())->test(ProductManager::class)
            ->call('handleScannedBarcode', '5055416391889')
            ->assertSet('currentProduct.name', 'Golden Jojoba Oil');

        Http::assertNothingSent();
    }

    public function test_qr_link_codes_are_shared_too(): void
    {
        $qr = 'https://doterra.me/a8F3kQ2';
        $this->saveProduct(User::factory()->create(), $qr, 'Breathe');

        Livewire::actingAs(User::factory()->create())->test(ProductManager::class)
            ->call('handleScannedBarcode', $qr)
            ->assertSet('currentProduct.name', 'Breathe');
    }

    public function test_editing_updates_the_entry(): void
    {
        $user = User::factory()->create();
        $this->saveProduct($user, '60204650', 'Lavendr');
        $product = Product::where('barcode', '60204650')->firstOrFail();

        Livewire::actingAs($user)->test(ProductManager::class)
            ->call('editProduct', $product->id)
            ->set('currentProduct.name', 'Lavender')
            ->call('saveProduct');

        $this->assertSame('Lavender', CatalogEntry::where('code', '60204650')->value('name'));
        $this->assertSame(1, CatalogEntry::count());
    }

    public function test_sync_command_backfills_from_existing_and_deleted_products(): void
    {
        Product::factory()->create(['barcode' => '60204656', 'name' => 'Lemon']);
        Product::factory()->create(['barcode' => '60204660', 'name' => 'Tea Tree'])->delete();
        Product::factory()->create(['barcode' => null]);

        $this->artisan('catalog:sync')->assertSuccessful();
        $this->artisan('catalog:sync')->assertSuccessful(); // idempotent

        $this->assertSame(2, CatalogEntry::count());
        $this->assertSame('Tea Tree', CatalogEntry::where('code', '60204660')->value('name'));
    }

    public function test_deleting_the_account_keeps_the_anonymous_entry(): void
    {
        $user = User::factory()->create();
        $this->saveProduct($user, '60204650');

        $user->forceDelete();

        $this->assertSame('Lavender', CatalogEntry::where('code', '60204650')->value('name'));
    }

    public function test_form_and_privacy_policy_explain_the_catalog(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ProductManager::class)
            ->call('createProduct')
            ->assertSee('gemeinsamen Katalog');

        $this->get(route('legal.privacy'))->assertSee('Gemeinsamer Code-Katalog')->assertSee('ohne Bezug zu deinem Konto');
    }
}
