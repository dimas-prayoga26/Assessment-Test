<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrandingIconTest extends TestCase
{
    public function test_branding_icon_uses_logo_for_current_host(): void
    {
        $response = $this
            ->withHeader('Host', 'siap.rnb.co.id')
            ->get('http://siap.rnb.co.id/upload-file/verify-applicant');

        $response
            ->assertOk()
            ->assertSee('images/Logo%20RNB.png', false);
    }

    public function test_branding_icon_uses_default_logo_for_unknown_host(): void
    {
        $response = $this
            ->withHeader('Host', '127.0.0.1')
            ->get('http://127.0.0.1/upload-file/verify-applicant');

        $response
            ->assertOk()
            ->assertSee('images/images.png', false);
    }
}
