<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_redirects_guests_to_login_from_the_root_url(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
