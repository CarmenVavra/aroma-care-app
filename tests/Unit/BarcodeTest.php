<?php

namespace Tests\Unit;

use App\Enums\ProductCategory;
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
            'EAN-13' => ['4260123456785', '4260123456785'],
            'UPC-A' => ['812345678905', '812345678905'],
            'with whitespace' => ["  4260123456785\n", '4260123456785'],
            'GTIN-14 with leading zero' => ['04260123456785', '4260123456785'],
            'GS1 element string, bracketed' => ['(01)04260123456785(10)LOT42', '4260123456785'],
            'GS1 DataMatrix with symbology id' => ["]d2010426012345678510LOT\x1D17290101", '4260123456785'],
            'GS1 Digital Link' => ['https://id.example.com/01/04260123456785/10/LOT42?exp=2901', '4260123456785'],
            'article number (Code 128)' => ['60200133', '60200133'],
            'alphanumeric Code 39' => ['AB-1234/5', 'AB-1234/5'],
            'plain URL' => ['https://www.example.com/lavender?ref=qr', null],
            'invalid characters' => ['<script>', null],
            'too long' => [str_repeat('1', 65).'A', null],
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
        $this->assertSame(['812345678905', '0812345678905'], Barcode::variants('812345678905'));
        $this->assertSame(['0812345678905', '812345678905'], Barcode::variants('0812345678905'));
        $this->assertSame(['4260123456785'], Barcode::variants('4260123456785'));
        $this->assertSame(['AB-1'], Barcode::variants('AB-1'));
    }

    public function test_parses_essential_oil_title_into_german_name(): void
    {
        $s = (new ProductTitleParser)->parse('UPCitemdb', 'doTERRA Lavender Essential Oil - 15 mL', 'doTERRA');

        $this->assertSame('Lavendel', $s->name);
        $this->assertSame('doTERRA', $s->brand);
        $this->assertSame(ProductCategory::EssentialOil, $s->category);
        $this->assertSame(15.0, $s->quantity);
        $this->assertSame('ml', $s->unit);
        $this->assertSame('Lavandula angustifolia', $s->latinName);
    }

    public function test_prefers_the_longer_oil_name(): void
    {
        $s = (new ProductTitleParser)->parse('x', 'Wild Orange Essential Oil 15ml');

        $this->assertSame('Wilde Orange', $s->name);
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
}
