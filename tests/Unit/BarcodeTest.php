<?php

namespace Tests\Unit;

use App\Enums\ProductCategory;
use App\Services\ProductLookup\CodeHints;
use App\Services\ProductLookup\ProductTitleParser;
use App\Support\Barcode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BarcodeTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function codes(): array
    {
        return [
            'EAN-13' => ['4260123456788', '4260123456788'],
            'UPC-A' => ['812345678901', '812345678901'],
            'with whitespace' => ["  4260123456788\n", '4260123456788'],
            'GTIN-14 with leading zero' => ['04260123456788', '4260123456788'],
            'GS1 element string, bracketed' => ['(01)04260123456788(10)LOT42', '4260123456788'],
            'GS1 DataMatrix with symbology id' => ["]d2010426012345678810LOT\x1D17290101", '4260123456788'],
            'GS1 Digital Link' => ['https://id.example.com/01/04260123456788/10/LOT42?exp=2901', '4260123456788'],
            'article number (Code 128)' => ['60200133', '60200133'],
            'alphanumeric Code 39' => ['AB-1234/5', 'AB-1234/5'],
            'QR code with a link is kept' => ['https://www.example.com/lavender?ref=qr&x=1', 'https://www.example.com/lavender?ref=qr&x=1'],
            'QR code with text' => ["Lavender\nLot 4521 – 15 ml", 'Lavender Lot 4521 – 15 ml'],
            'Digital Link without scheme' => ['www.example.com/01/04260123456788', '4260123456788'],
            'too long' => [str_repeat('a', 513), null],
            'empty' => ['   ', null],
        ];
    }

    #[DataProvider('codes')]
    public function test_normalize(string $raw, ?string $expected): void
    {
        $this->assertSame($expected, Barcode::normalize($raw));
    }

    public function test_upc_and_ean_variants(): void
    {
        $this->assertSame(['812345678901', '0812345678901'], Barcode::variants('812345678901'));
        $this->assertSame(['0812345678901', '812345678901'], Barcode::variants('0812345678901'));
        $this->assertSame(['4260123456788'], Barcode::variants('4260123456788'));
        $this->assertSame(['AB-1'], Barcode::variants('AB-1'));
    }

    public function test_parses_essential_oil_title(): void
    {
        $s = (new ProductTitleParser)->parse('UPCitemdb', 'doTERRA Lavender Essential Oil - 15 mL', 'doTERRA');

        $this->assertSame('Lavender', $s->name, 'names stay as printed on the bottle');
        $this->assertSame('doTERRA', $s->brand);
        $this->assertSame(ProductCategory::EssentialOil, $s->category);
        $this->assertSame(15.0, $s->quantity);
        $this->assertSame('ml', $s->unit);
        $this->assertSame('Lavandula angustifolia', $s->latinName);
    }

    public function test_keeps_original_names_and_finds_botanical_name(): void
    {
        $parser = new ProductTitleParser;

        $orange = $parser->parse('x', 'Wild Orange Essential Oil 15ml');
        $this->assertSame('Wild Orange', $orange->name);
        $this->assertSame('Citrus sinensis', $orange->latinName);

        $tea = $parser->parse('x', 'doTERRA Tea Tree Essential Oil 15 mL', 'doTERRA');
        $this->assertSame('Tea Tree', $tea->name);
        $this->assertSame('Melaleuca alternifolia', $tea->latinName);
    }

    public function test_blend_keeps_its_name(): void
    {
        $s = (new ProductTitleParser)->parse('x', 'doTERRA On Guard Protective Blend 15 mL', 'doTERRA');

        $this->assertSame('On Guard Protective Blend', $s->name);
        $this->assertNull($s->category);
        $this->assertSame(15.0, $s->quantity);
    }

    public function test_detects_carrier_oil_wax_and_fluid_ounces(): void
    {
        $parser = new ProductTitleParser;

        $jojoba = $parser->parse('x', 'Bio Jojobaöl kaltgepresst', 'Primavera', '100 ml');
        $this->assertSame(ProductCategory::CarrierOil, $jojoba->category);
        $this->assertSame('Bio Jojobaöl kaltgepresst', $jojoba->name);
        $this->assertSame(100.0, $jojoba->quantity);

        $this->assertSame(ProductCategory::Wax, $parser->parse('x', 'Bienenwachs Pastillen')->category);
        $this->assertSame(30.0, $parser->parse('x', 'Peppermint Oil 1 fl oz')->quantity);
    }

    public function test_keeps_title_when_it_equals_the_brand(): void
    {
        $this->assertSame('Nutella', (new ProductTitleParser)->parse('x', 'Nutella', 'Nutella, Ferrero')->name);
    }

    public function test_takes_first_of_several_brands(): void
    {
        $this->assertSame('Primavera', (new ProductTitleParser)->parse('x', 'Lavendel', 'Primavera, Primavera Life')->brand);
    }

    public function test_shop_title_is_cut_before_marketing_text(): void
    {
        $s = (new ProductTitleParser)->parse(
            'x',
            'Naissance Cold Pressed Golden Jojoba Oil (no. 233) 500ml - Pure & Natural, Unrefined, Vegan',
            'Naissance',
        );

        $this->assertSame('Cold Pressed Golden Jojoba Oil (no. 233)', $s->name);
        $this->assertSame(ProductCategory::CarrierOil, $s->category);
        $this->assertSame(500.0, $s->quantity);
    }

    public function test_gtin_check_digit(): void
    {
        $this->assertTrue(Barcode::isGtin('5055416391889'));
        $this->assertTrue(Barcode::isGtin('885909950805'));
        $this->assertTrue(Barcode::isGtin('40123455'));
        $this->assertFalse(Barcode::isGtin('60204656'), 'doTERRA item number is not an EAN-8');
        $this->assertFalse(Barcode::isGtin('5055416391880'));
        $this->assertFalse(Barcode::isGtin('AB-1234'));
    }

    public function test_code_hints_for_brand_article_numbers(): void
    {
        $doterra = (new CodeHints)->forCode('60204656');
        $this->assertSame('doTERRA', $doterra?->brand);
        $this->assertSame(ProductCategory::EssentialOil, $doterra->category);
        $this->assertNull($doterra->name);

        $this->assertSame('Naissance', (new CodeHints)->forCode('5055416391889')?->brand);
        $this->assertNull((new CodeHints)->forCode('4260123456788'));
    }

    public function test_code_hints_from_qr_links(): void
    {
        $hints = new CodeHints;

        $breathe = $hints->forCode('https://www.doterra.com/US/en/p/breathe-oil');
        $this->assertSame('doTERRA', $breathe?->brand);
        $this->assertSame('Breathe', $breathe->name);

        // Random tokens are not taken as a name, the brand still is.
        $token = $hints->forCode('https://doterra.me/a8F3kQ2');
        $this->assertSame('doTERRA', $token?->brand);
        $this->assertNull($token->name);

        $this->assertSame('Golden Jojoba', $hints->forCode('www.naissance.com/products/golden-jojoba-oil')?->name);
        $this->assertNull($hints->forCode('https://example.com/x7Y9'));
        $this->assertNull($hints->forCode('Lot 4521'));
    }
}
