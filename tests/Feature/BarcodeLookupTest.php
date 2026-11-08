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
            'world.openproductsfacts.org/*' => Http::response(['status' => 0]),
            'api.upcitemdb.com/*' => Http::response(['code' => 'OK', 'total' => count($items), 'items' => $items]),
        ]);
    }

    public function test_unknown_barcode_prefills_form_from_upcitemdb(): void
    {
        $this->fakeNotFoundExceptUpc([['title' => 'doTERRA Lavender Essential Oil - 15 mL', 'brand' => 'doTERRA']]);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '812345678901')
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.barcode', '812345678901')
            ->assertSet('currentProduct.name', 'Lavender')
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
            ->call('handleScannedBarcode', '4260123456788')
            ->assertSet('currentProduct.name', 'Jojobaöl Bio')
            ->assertSet('currentProduct.brand', 'Primavera')
            ->assertSet('currentProduct.category', 'carrier_oil')
            ->assertSet('currentProduct.quantity', '100')
            ->assertSet('suggestionSource', 'Open Beauty Facts');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/api/v2/product/4260123456788')
            && str_contains($request->header('User-Agent')[0], 'AromaCare'));
    }

    public function test_upc_code_is_also_tried_as_ean13(): void
    {
        $this->fakeNotFoundExceptUpc();

        app(ProductLookup::class)->find(['812345678901', '0812345678901']);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'openbeautyfacts.org/api/v2/product/0812345678901'));
    }

    public function test_no_data_found_still_opens_form_with_barcode(): void
    {
        $this->fakeNotFoundExceptUpc();

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '4260123456788')
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.barcode', '4260123456788')
            ->assertSet('currentProduct.name', '')
            ->assertSet('suggestionSource', null)
            ->assertDispatched('notify', message: 'Neuer Code – keine Produktdaten gefunden. Bitte Produkt erfassen.');
    }

    public function test_lookup_results_and_misses_are_cached(): void
    {
        $this->fakeNotFoundExceptUpc();

        app(ProductLookup::class)->find(['4260123456788']);
        app(ProductLookup::class)->find(['4260123456788']);

        Http::assertSentCount(4); // one round over the four sources, then the cached miss
    }

    public function test_unreachable_database_does_not_break_the_scan(): void
    {
        Http::fake([
            'world.openbeautyfacts.org/*' => fn () => throw new ConnectionException('timeout'),
            'world.openfoodfacts.org/*' => Http::response(['status' => 0]),
            'world.openproductsfacts.org/*' => Http::response(['status' => 0]),
            'api.upcitemdb.com/*' => Http::response([], 429),
        ]);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '4260123456788')
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.barcode', '4260123456788');

        $this->assertNull(Cache::get('product-lookup:v2:4260123456788'), 'a miss with unreachable sources must not be cached');
    }

    public function test_lookup_can_be_disabled(): void
    {
        config(['aromacare.lookup.enabled' => false]);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '4260123456788')
            ->assertSet('isModalOpen', true);

        Http::assertNothingSent();
    }

    public function test_previously_deleted_product_is_taken_over_without_lookup(): void
    {
        $old = Product::factory()->for($this->user)->essentialOil()->create([
            'name' => 'Lavendel', 'brand' => 'doTERRA', 'barcode' => '812345678901',
            'quantity' => 0.5, 'initial_quantity' => 15,
        ]);
        $old->delete();

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '0812345678901')
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
            ->call('handleScannedBarcode', '812345678901')
            ->assertSet('currentProduct.name', 'Mein Name')
            ->assertSet('currentProduct.category', 'carrier_oil')
            ->assertSet('currentProduct.brand', 'doTERRA')
            ->assertSet('currentProduct.barcode', '812345678901');
    }

    public function test_upc_scan_finds_product_stored_as_ean13(): void
    {
        $product = Product::factory()->for($this->user)->create(['barcode' => '0812345678901', 'category' => ProductCategory::Wax]);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '812345678901')
            ->assertSet('selectedProductId', $product->id)
            ->assertSet('isModalOpen', false);

        Http::assertNothingSent();
    }

    public function test_gs1_datamatrix_is_reduced_to_the_gtin(): void
    {
        $product = Product::factory()->for($this->user)->create(['barcode' => '4260123456788']);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', "]d2010426012345678810LOT\x1D17290101")
            ->assertSet('selectedProductId', $product->id);
    }

    public function test_qr_code_with_link_becomes_the_product_code_and_is_found_again(): void
    {
        $qr = 'https://www.example.com/source-to-you?lot=ABC&product=lavender_15ml';

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', $qr)
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.barcode', $qr)
            ->set('currentProduct.name', 'Lavender')
            ->set('currentProduct.quantity', '15')
            ->call('saveProduct')
            ->assertHasNoErrors();

        // Links and other QR content are never sent to product databases.
        Http::assertNothingSent();

        $product = Product::where('barcode', $qr)->firstOrFail();

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', $qr)
            ->assertSet('selectedProductId', $product->id);
    }

    public function test_long_qr_content_is_accepted_up_to_the_limit(): void
    {
        $qr = 'https://www.example.com/'.str_repeat('a', 400);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', $qr)
            ->assertSet('currentProduct.barcode', $qr);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', str_repeat('a', 513))
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify', message: 'Es wurde kein lesbarer Code erkannt. Bitte erneut scannen.');
    }

    public function test_doterra_item_number_prefills_brand_without_asking_databases(): void
    {
        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '60204656')
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.barcode', '60204656')
            ->assertSet('currentProduct.brand', 'doTERRA')
            ->assertSet('currentProduct.category', 'essential_oil')
            ->assertSet('currentProduct.name', '')
            ->assertSet('suggestionSource', 'dem Code')
            ->assertDispatched('notify', message: 'Marke aus dem Code erkannt – bitte Namen und Menge ergänzen.');

        // Not a valid EAN: no request (UPCitemdb would only answer "invalid" and use up the daily quota).
        Http::assertNothingSent();
    }

    public function test_unknown_naissance_ean_gets_brand_from_company_prefix(): void
    {
        Http::fake([
            'world.openbeautyfacts.org/*' => Http::response(['status' => 0]),
            'world.openfoodfacts.org/*' => Http::response(['status' => 0]),
            'world.openproductsfacts.org/*' => Http::response(['status' => 0]),
            'api.upcitemdb.com/*' => Http::response(['code' => 'OK', 'total' => 0, 'items' => []]),
        ]);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '5055416391889')
            ->assertSet('currentProduct.brand', 'Naissance')
            ->assertSet('currentProduct.name', '');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'openproductsfacts.org'));
    }

    public function test_breathe_qr_link_prefills_brand_and_name(): void
    {
        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', 'https://www.doterra.com/US/en/p/breathe-oil')
            ->assertSet('currentProduct.brand', 'doTERRA')
            ->assertSet('currentProduct.name', 'Breathe')
            ->assertSet('currentProduct.category', 'essential_oil');

        Http::assertNothingSent();
    }

    public function test_privacy_policy_mentions_the_lookup(): void
    {
        $this->get(route('legal.privacy'))->assertSee('Produktdaten-Suche')->assertSee('UPCitemdb');

        config(['aromacare.lookup.enabled' => false]);
        $this->get(route('legal.privacy'))->assertDontSee('Produktdaten-Suche:');
    }
}
