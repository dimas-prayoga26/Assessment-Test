<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_root_domain_returns_custom_not_found_page(): void
    {
        $response = $this->get('http://technical-test.trah.co.id/');

        $response
            ->assertNotFound()
            ->assertSee('The page you were looking for is not found!')
            ->assertSee('gymove/assets/css/style.css');
    }

    public function test_unknown_url_returns_custom_not_found_page(): void
    {
        $response = $this->get('/ngide-akses-url');

        $response
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('Back to Home');
    }
}
