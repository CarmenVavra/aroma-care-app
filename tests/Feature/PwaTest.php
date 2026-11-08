<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_manifest_meets_install_requirements(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('AromaCare', $manifest['name']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);

        $sizes = collect($manifest['icons'])->pluck('sizes')->all();
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        $this->assertContains('maskable', collect($manifest['icons'])->pluck('purpose')->all());

        foreach ($manifest['icons'] as $icon) {
            $file = public_path(ltrim($icon['src'], '/'));
            $this->assertFileExists($file);
            [$width, $height] = getimagesize($file);
            $this->assertSame($icon['sizes'], "{$width}x{$height}");
            $this->assertStringNotContainsString('/icons/', $icon['src'], 'netcup/Apache reserves /icons/');
        }
    }

    public function test_service_worker_and_offline_page_exist(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertStringContainsString("addEventListener('fetch'", (string) file_get_contents(public_path('sw.js')));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('app-icons/apple-touch-icon.png'));
    }

    public function test_all_layouts_link_the_manifest(): void
    {
        $this->get(route('login'))->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false);
        $this->get(route('legal.imprint'))->assertSee('apple-touch-icon', false);
        $this->actingAs(User::factory()->create())
            ->get(route('stock'))
            ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false);
    }

    public function test_profile_explains_how_to_install(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile'))
            ->assertSee('App installieren')
            ->assertSee('Zum Home-Bildschirm')
            ->assertSee('x-data="installApp"', false);
    }
}
