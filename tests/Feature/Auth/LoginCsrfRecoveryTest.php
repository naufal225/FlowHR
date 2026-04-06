<?php

namespace Tests\Feature\Auth;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginCsrfRecoveryTest extends TestCase
{
    public function test_login_page_sets_no_store_headers_and_renders_csrf_input(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT')
            ->assertSee('name="_token"', false);

        $cacheControl = (string) $response->headers->get('Cache-Control', '');
        $this->assertTrue(Str::contains($cacheControl, 'no-store'));
        $this->assertTrue(Str::contains($cacheControl, 'no-cache'));
        $this->assertTrue(Str::contains($cacheControl, 'must-revalidate'));
    }

    public function test_login_page_regenerates_csrf_token_each_visit(): void
    {
        $firstResponse = $this->get(route('login'));
        $firstToken = $this->extractTokenFromHtml($firstResponse->getContent());

        $secondResponse = $this->get(route('login'));
        $secondToken = $this->extractTokenFromHtml($secondResponse->getContent());

        $this->assertNotNull($firstToken);
        $this->assertNotNull($secondToken);
        $this->assertNotSame($firstToken, $secondToken);
    }

    public function test_token_mismatch_redirects_to_login_with_recovery_message(): void
    {
        Route::post('/_test-csrf-mismatch', static function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        })->middleware('web');

        $response = $this->post('/_test-csrf-mismatch');

        $response->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Sesi keamanan halaman sudah kedaluwarsa. Silakan login ulang.');

        $cacheControl = (string) $response->headers->get('Cache-Control', '');
        $this->assertTrue(Str::contains($cacheControl, 'no-store'));
    }

    private function extractTokenFromHtml(string $html): ?string
    {
        if (! preg_match('/name="_token"\s+value="([^"]+)"/', $html, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
