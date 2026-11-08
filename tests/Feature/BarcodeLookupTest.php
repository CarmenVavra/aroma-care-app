<?php

namespace Tests\Feature;

use App\Enums\ProductCategory;
use App\Livewire\ProductManager;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductLookup\ProductLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class BarcodeLookupTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Cache::flush();
        config(['aromacare.lookup.enabled' => true]); // disabled by default in phpunit.xml
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function fakeNotFoundExceptUpc(array $items = []): void
    {
        Http::fake([
            'world.openbeautyfacts.org/*' => Http::response(['status' => 0]),
            'world.openfoodfacts.org/*' => Http::response(['status' => 0]),
            'api.upcitemdb.com/*' => Http::response(['code' => 'OK', 'total' => count($items), 'items' => $items]),
        ]);
    }

    public function test_unknown_barcode_prefills_form_from_upcitemdb(): void
    {
        $this->fakeNotFoundExceptUpc([['title' => 'doTERRA Lavender Essential Oil - 15 mL', 'brand' => 'doTERRA']]);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '812345678905')
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.barcode', '812345678905')
            ->assertSet('currentProduct.name', 'Lavendel')
            ->assertSet('currentProduct.brand', 'doTERRA')
            ->assertSet('currentProduct.category', 'essential_oil')
            ->assertSet('currentProduct.quantity', '15')
            ->assertSet('currentProduct.latin_name', 'Lavandula angustifolia')
            ->assertSet('suggestionSource', 'UPCitemdb')
            ->assertSee('Vorschlag aus')
            ->assertDispatched('notify', message: 'Produktdaten aus UPCitemdb übernommen – bitte prüfen.');
    }

    public function test_open_beauty_facts_is_asked_first(): void
    {
        Http::fake([
            'world.openbeautyfacts.org/*' => Http::response(['status' => 1, 'product' => [
                'product_name' => 'Jojobaöl Bio', 'brands' => 'Primavera,Primavera Life', 'quantity' => '100 ml',
            ]]),
        ]);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '4260123456785')
            ->assertSet('currentProduct.name', 'Jojobaöl Bio')
            ->assertSet('currentProduct.brand', 'Primavera')
            ->assertSet('currentProduct.category', 'carrier_oil')
            ->assertSet('currentProduct.quantity', '100')
            ->assertSet('suggestionSource', 'Open Beauty Facts');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/api/v2/product/4260123456785')
            && str_contains($request->header('User-Agent')[0], 'AromaCare'));
    }

    public function test_upc_code_is_also_tried_as_ean13(): void
    {
        $this->fakeNotFoundExceptUpc();

        app(ProductLookup::class)->find(['812345678905', '0812345678905']);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'openbeautyfacts.org/api/v2/product/0812345678905'));
    }

    public function test_no_data_found_still_opens_form_with_barcode(): void
    {
        $this->fakeNotFoundExceptUpc();

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '4260123456785')
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.barcode', '4260123456785')
            ->assertSet('currentProduct.name', '')
            ->assertSet('suggestionSource', null)
            ->assertDispatched('notify', message: 'Neuer Barcode – keine Produktdaten gefunden. Bitte Produkt erfassen.');
    }

    public function test_lookup_results_and_misses_are_cached(): void
    {
        $this->fakeNotFoundExceptUpc();

        app(ProductLookup::class)->find(['4260123456785']);
        app(ProductLookup::class)->find(['4260123456785']);

        Http::assertSentCount(3); // one round over the three sources, then the cached miss
    }

    public function test_unreachable_database_does_not_break_the_scan(): void
    {
        Http::fake([
            'world.openbeautyfacts.org/*' => fn () => throw new ConnectionException('timeout'),
            'world.openfoodfacts.org/*' => Http::response(['status' => 0]),
            'api.upcitemdb.com/*' => Http::response([], 429),
        ]);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '4260123456785')
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.barcode', '4260123456785');

        $this->assertNull(Cache::get('product-lookup:v1:4260123456785'), 'a miss with unreachable sources must not be cached');
    }

    public function test_lookup_can_be_disabled(): void
    {
        config(['aromacare.lookup.enabled' => false]);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '4260123456785')
            ->assertSet('isModalOpen', true);

        Http::assertNothingSent();
    }

    public function test_previously_deleted_product_is_taken_over_without_lookup(): void
    {
        $old = Product::factory()->for($this->user)->essentialOil()->create([
            'name' => 'Lavendel', 'brand' => 'doTERRA', 'barcode' => '812345678905',
            'quantity' => 0.5, 'initial_quantity' => 15,
        ]);
        $old->delete();

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '0812345678905')
            ->assertSet('currentProduct.id', null)
            ->assertSet('currentProduct.name', 'Lavendel')
            ->assertSet('currentProduct.brand', 'doTERRA')
            ->assertSet('currentProduct.quantity', '15')
            ->assertSet('currentProduct.expires_at', '')
            ->assertSet('currentProduct.latin_name', 'Lavandula angustifolia')
            ->assertSet('suggestionSource', 'deinem früheren Eintrag');

        Http::assertNothingSent();
    }

    public function test_scan_inside_open_form_only_fills_empty_fields(): void
    {
        $this->fakeNotFoundExceptUpc([['title' => 'doTERRA Lavender Essential Oil 15 mL', 'brand' => 'doTERRA']]);

        Livewire::test(ProductManager::class)
            ->call('createProduct')
            ->set('currentProduct.category', 'carrier_oil')
            ->set('currentProduct.name', 'Mein Name')
            ->call('handleScannedBarcode', '812345678905')
            ->assertSet('currentProduct.name', 'Mein Name')
            ->assertSet('currentProduct.category', 'carrier_oil')
            ->assertSet('currentProduct.brand', 'doTERRA')
            ->assertSet('currentProduct.barcode', '812345678905');
    }

    public function test_upc_scan_finds_product_stored_as_ean13(): void
    {
        $product = Product::factory()->for($this->user)->create(['barcode' => '0812345678905', 'category' => ProductCategory::Wax]);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '812345678905')
            ->assertSet('selectedProductId', $product->id)
            ->assertSet('isModalOpen', false);

        Http::assertNothingSent();
    }

    public function test_gs1_datamatrix_is_reduced_to_the_gtin(): void
    {
        $product = Product::factory()->for($this->user)->create(['barcode' => '4260123456785']);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', "]d2010426012345678510LOT\x1D17290101")
            ->assertSet('selectedProductId', $product->id);
    }

    public function test_qr_code_with_plain_link_gets_a_helpful_message(): void
    {
        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', 'https://www.example.com/source-to-you?lot=ABC')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify', message: 'Dieser QR-Code enthält nur einen Link, keinen Produktcode. Bitte scanne den Strichcode auf dem Etikett.');

        Http::assertNothingSent();
    }

    public function test_privacy_policy_mentions_the_lookup(): void
    {
        $this->get(route('legal.privacy'))->assertSee('Produktdaten-Suche')->assertSee('UPCitemdb');

        config(['aromacare.lookup.enabled' => false]);
        $this->get(route('legal.privacy'))->assertDontSee('Produktdaten-Suche:');
    }
}
