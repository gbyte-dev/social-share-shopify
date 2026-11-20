<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test that the public landing page renders successfully with all primary sections.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Socialvire');
        $response->assertSeeText('Make Every Product');
        $response->assertSee('Socialvire helps your Shopify store connect products with the social platforms your customers already use.');
        $response->assertSee('Add customizable floating social icons and convenient product-sharing options.');
        $response->assertSee('How It Works');
        $response->assertSee('Frequently Asked Questions');
    }
}
