<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_page_returns_not_found(): void
    {
        $response = $this->get('/');

        $response->assertNotFound();
    }
}
