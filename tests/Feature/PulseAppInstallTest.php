<?php

namespace Tests\Feature;

use Tests\TestCase;

class PulseAppInstallTest extends TestCase
{
    public function test_public_manifest_opens_the_login(): void
    {
        $response = $this->get('/pulse/manifest.webmanifest');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/manifest+json');
        $manifest = $response->json();
        $this->assertSame('/pulse', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('El Pulso del Día', $manifest['name']);
        $this->assertNotEmpty($manifest['icons']);
    }

    public function test_public_service_worker_is_installable(): void
    {
        $response = $this->get('/pulse/sw');

        $response->assertOk();
        $this->assertNull($response->headers->get('Set-Cookie'));
        $this->assertStringContainsString('javascript', (string) $response->headers->get('Content-Type'));
        $response->assertSee('fetch', false);
        $response->assertSee('install', false);
    }

    public function test_landing_offers_install_and_login_links_the_manifest(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/pulse/manifest.webmanifest', false)
            ->assertSee('Descargar El Pulso del Día', false)
            ->assertSee('beforeinstallprompt', false);

        $this->get('/pulse')
            ->assertOk()
            ->assertSee('/pulse/manifest.webmanifest', false)
            ->assertSee('instalar', false);
    }
}
