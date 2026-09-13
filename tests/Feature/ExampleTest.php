<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_homepage_redirects_to_the_main_route()
    {
        $response = $this->get('/');

        $response->assertRedirect(route('main'));
    }
}
