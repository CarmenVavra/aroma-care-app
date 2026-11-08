<?php

namespace Tests\Feature;

use App\Enums\Absorption;
use App\Enums\ProductCategory;
use App\Livewire\ProductManager;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_stock_page_renders_the_component(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSeeLivewire(ProductManager::class)
            ->assertSee('Bestand');
    }

    public function test_products_are_filtered_by_search_and_category(): void
    {
        Product::factory()->for($this->user)->essentialOil()->create(['name' => 'Lavendel']);
        Product::factory()->for($this->user)->carrierOil()->create(['name' => 'Jojobaöl']);
        Product::factory()->for($this->user)->create(['name' => 'Bienenwachs', 'category' => ProductCategory::Wax]);

        Livewire::test(ProductManager::class)
            ->assertSee('Lavendel')->assertSee('Jojobaöl')->assertSee('Bienenwachs')
            ->set('search', 'Lav')
            ->assertSee('Lavendel')->assertDontSee('Jojobaöl')
            ->set('search', '')
            ->set('selectedCategory', 'carrier_oil')
            ->assertSee('Jojobaöl')->assertDontSee('Lavendel')->assertDontSee('Bienenwachs');
    }

    public function test_search_matches_effect_tags_and_barcode(): void
    {
        Product::factory()->for($this->user)->essentialOil()->create(['name' => 'Lavendel', 'barcode' => '4260123456785']);
        Product::factory()->for($this->user)->create(['name' => 'Vitamin E']);

        Livewire::test(ProductManager::class)
            ->set('search', 'Beruhigend')
            ->assertSee('Lavendel')->assertDontSee('Vitamin E')
            ->set('search', '4260123456785')
            ->assertSee('Lavendel')->assertDontSee('Vitamin E');
    }

    public function test_invalid_category_filter_is_ignored(): void
    {
        Product::factory()->for($this->user)->create(['name' => 'Vitamin E']);

        Livewire::test(ProductManager::class)
            ->set('selectedCategory', "x' OR 1=1 --")
            ->assertSet('selectedCategory', '')
            ->assertSee('Vitamin E');
    }

    public function test_it_creates_an_essential_oil_with_details(): void
    {
        Livewire::test(ProductManager::class)
            ->call('createProduct')
            ->assertSet('isModalOpen', true)
            ->set('currentProduct.category', 'essential_oil')
            ->set('currentProduct.name', 'Lavendel')
            ->set('currentProduct.brand', 'doTERRA')
            ->set('currentProduct.quantity', '15')
            ->set('currentProduct.unit', 'ml')
            ->set('currentProduct.expires_at', '03/2029')
            ->set('currentProduct.latin_name', 'Lavandula angustifolia')
            ->call('addTag', 'effects_mental', 'Beruhigend')
            ->call('addTag', 'effects_physical', 'Entspannend')
            ->set('currentProduct.usage_types', ['Verdampfen', 'Hautpflege'])
            ->call('saveProduct')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');

        $product = Product::with('essentialOilDetail')->sole();

        $this->assertSame('Lavendel', $product->name);
        $this->assertSame(ProductCategory::EssentialOil, $product->category);
        $this->assertSame('2029-03-31', $product->expires_at->toDateString());
        $this->assertEquals(15, $product->initial_quantity);
        $this->assertSame('Lavandula angustifolia', $product->essentialOilDetail->latin_name);
        $this->assertSame(['Beruhigend'], $product->essentialOilDetail->effects_mental);
        $this->assertSame(['Entspannend'], $product->essentialOilDetail->effects_physical);
        $this->assertSame(['Verdampfen', 'Hautpflege'], $product->essentialOilDetail->usage_types);
        $this->assertDatabaseCount('carrier_oil_details', 0);
    }

    public function test_it_creates_a_carrier_oil_with_details(): void
    {
        Livewire::test(ProductManager::class)
            ->call('createProduct')
            ->set('currentProduct.category', 'carrier_oil')
            ->set('currentProduct.name', 'Jojobaöl')
            ->set('currentProduct.quantity', '100')
            ->set('currentProduct.comedogenic_rating', 2)
            ->set('currentProduct.absorption', 'medium')
            ->set('currentProduct.use_areas', ['Gesicht', 'Massage'])
            ->call('saveProduct')
            ->assertHasNoErrors();

        $detail = Product::with('carrierOilDetail')->sole()->carrierOilDetail;

        $this->assertSame(2, $detail->comedogenic_rating);
        $this->assertSame(Absorption::Medium, $detail->absorption);
        $this->assertSame(['Gesicht', 'Massage'], $detail->use_areas);
        $this->assertDatabaseCount('essential_oil_details', 0);
    }

    public function test_validation_errors_are_reported(): void
    {
        Livewire::test(ProductManager::class)
            ->call('createProduct')
            ->set('currentProduct.category', 'carrier_oil')
            ->set('currentProduct.name', '')
            ->set('currentProduct.quantity', '0')
            ->set('currentProduct.expires_at', '2029-03')
            ->set('currentProduct.comedogenic_rating', 7)
            ->set('currentProduct.unit', 'liter')
            ->call('saveProduct')
            ->assertHasErrors([
                'currentProduct.name' => 'required',
                'currentProduct.quantity' => 'gt',
                'currentProduct.expires_at' => 'regex',
                'currentProduct.comedogenic_rating' => 'between',
                'currentProduct.unit' => 'in',
            ])
            ->assertSet('isModalOpen', true);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_barcode_must_be_unique_among_active_products(): void
    {
        Product::factory()->for($this->user)->create(['barcode' => '4260123456785']);

        Livewire::test(ProductManager::class)
            ->call('createProduct', '4260123456785')
            ->set('currentProduct.name', 'Duplikat')
            ->set('currentProduct.quantity', '10')
            ->call('saveProduct')
            ->assertHasErrors(['currentProduct.barcode' => 'unique']);
    }

    public function test_edit_loads_product_and_switching_category_replaces_details(): void
    {
        $product = Product::factory()->for($this->user)->essentialOil()->create(['name' => 'Lavendel', 'quantity' => 12, 'initial_quantity' => 15]);

        Livewire::test(ProductManager::class)
            ->call('editProduct', $product->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.id', $product->id)
            ->assertSet('currentProduct.name', 'Lavendel')
            ->assertSet('currentProduct.quantity', '12')
            ->assertSet('currentProduct.latin_name', 'Lavandula angustifolia')
            ->assertSet('currentProduct.effects_mental', ['Beruhigend', 'Ausgleichend'])
            ->set('currentProduct.category', 'carrier_oil')
            ->set('currentProduct.comedogenic_rating', 1)
            ->call('saveProduct')
            ->assertHasNoErrors();

        $product->refresh()->load(['essentialOilDetail', 'carrierOilDetail']);

        $this->assertSame(ProductCategory::CarrierOil, $product->category);
        $this->assertNull($product->essentialOilDetail);
        $this->assertSame(1, $product->carrierOilDetail->comedogenic_rating);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_delete_soft_deletes_the_product(): void
    {
        $product = Product::factory()->for($this->user)->create(['name' => 'Teebaum']);

        Livewire::test(ProductManager::class)
            ->call('showProduct', $product->id)
            ->call('deleteProduct', $product->id)
            ->assertSet('selectedProductId', null)
            ->assertDontSee('Teebaum');

        $this->assertSoftDeleted($product);
    }

    public function test_restock_adds_one_package(): void
    {
        $product = Product::factory()->for($this->user)->create(['quantity' => 2, 'initial_quantity' => 15]);

        Livewire::test(ProductManager::class)->call('restockProduct', $product->id);

        $this->assertEquals(17, $product->fresh()->quantity);
    }

    public function test_scanned_known_barcode_opens_detail_view(): void
    {
        $product = Product::factory()->for($this->user)->essentialOil()->create(['name' => 'Lavendel', 'barcode' => '4260123456785']);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', ' 4260123456785 ')
            ->assertSet('selectedProductId', $product->id)
            ->assertSet('openedByScan', true)
            ->assertSet('isModalOpen', false)
            ->assertSee('Produkt erkannt');
    }

    public function test_scanned_unknown_barcode_opens_prefilled_create_form(): void
    {
        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '4006381333931')
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.id', null)
            ->assertSet('currentProduct.barcode', '4006381333931')
            ->assertSee('Neues Produkt');
    }

    public function test_scanned_barcode_of_soft_deleted_product_opens_create_form(): void
    {
        Product::factory()->for($this->user)->create(['barcode' => '4006381333931'])->delete();

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '4006381333931')
            ->assertSet('isModalOpen', true)
            ->assertSet('currentProduct.barcode', '4006381333931');
    }

    public function test_scan_inside_open_form_keeps_entered_data(): void
    {
        Livewire::test(ProductManager::class)
            ->call('createProduct')
            ->set('currentProduct.name', 'Rosengeranie')
            ->call('handleScannedBarcode', '4006381333931')
            ->assertSet('currentProduct.name', 'Rosengeranie')
            ->assertSet('currentProduct.barcode', '4006381333931');
    }

    public function test_empty_scan_is_rejected(): void
    {
        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', "  \n ")
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify', type: 'error');
    }

    public function test_html_in_qr_content_is_only_shown_escaped(): void
    {
        $product = Product::factory()->for($this->user)->create(['barcode' => '<script>alert(1)</script>']);

        Livewire::test(ProductManager::class)
            ->call('handleScannedBarcode', '<script>alert(1)</script>')
            ->assertSet('selectedProductId', $product->id)
            ->assertDontSeeHtml('<script>alert(1)</script>')
            ->assertSee('<script>alert(1)</script>');
    }

    public function test_low_stock_filter(): void
    {
        Product::factory()->for($this->user)->create(['name' => 'Teebaum', 'quantity' => 2, 'initial_quantity' => 15]);
        Product::factory()->for($this->user)->create(['name' => 'Lavendel', 'quantity' => 12, 'initial_quantity' => 15]);

        Livewire::test(ProductManager::class)
            ->assertSee('1 Produkt fast leer')
            ->set('lowStockOnly', true)
            ->assertSee('Teebaum')
            ->assertDontSee('Lavendel');
    }
}
