<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config([
            'legal.name' => 'Erika Muster',
            'legal.postal_code' => '12345',
            'legal.city' => 'Musterstadt',
            'legal.country' => 'Deutschland',
            'legal.country_code' => 'DE',
            'legal.email' => 'erika@example.com',
            'legal.vat_id' => null,
            'legal.content_responsible' => 'Erika Muster',
        ]);
    }

    public function test_imprint_shows_legal_config_for_guests(): void
    {
        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Angaben gemäß § 5 DDG')
            ->assertSee('Erika Muster')
            ->assertSee('12345 Musterstadt')
            ->assertSee('erika@example.com')
            ->assertSee('§ 18 Abs. 2 MStV')
            ->assertDontSee('Umsatzsteuer-Identifikationsnummer')
            ->assertDontSee('Telefon');
    }

    public function test_imprint_uses_austrian_references(): void
    {
        config(['legal.country_code' => 'AT', 'legal.vat_id' => 'ATU12345678']);

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('§ 5 ECG')
            ->assertSee('§ 25 MedienG')
            ->assertSee('ATU12345678')
            ->assertDontSee('MStV');
    }

    public function test_privacy_policy_renders_for_guests_and_users(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Datenschutzerklärung')
            ->assertSee('erika@example.com')
            ->assertSee('§ 25 Abs. 2 Nr. 2 TDDDG')
            ->assertSee('Zur Anmeldung');

        $this->actingAs(User::factory()->create())
            ->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Zum Bestand');
    }

    public function test_privacy_policy_uses_austrian_references(): void
    {
        config(['legal.country_code' => 'AT']);

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('§ 165 Abs. 3 TKG 2021')
            ->assertSee('Österreichische Datenschutzbehörde');
    }

    public function test_legal_links_are_on_login_and_in_app(): void
    {
        $this->get(route('login'))
            ->assertSee(route('legal.imprint'))
            ->assertSee(route('legal.privacy'));

        $this->actingAs(User::factory()->create())
            ->get(route('stock'))
            ->assertSee(route('legal.imprint'))
            ->assertSee(route('legal.privacy'));
    }
}
