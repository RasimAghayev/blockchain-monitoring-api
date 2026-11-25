<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Confirms auth:api actually guards every Tokens/Transactions route
 * (docs/REPOSITORY_AUDIT.md P0-1) — not just that the happy path still works
 * once authenticated (already covered per-endpoint by TokenCrudTest,
 * TransactionCrudTest, etc.), but that an unauthenticated caller is rejected
 * on every one of them. No RefreshDatabase trait: every case 401s before
 * ever reaching a controller or the database.
 *
 * There is no 403 case to test alongside these 401s: TokenPolicy and
 * TransactionPolicy are deliberately permissive for any authenticated user
 * (docs/adr/0001-flat-authorization.md) — this domain has no ownership model
 * that could ever produce a 403 today.
 */
class AuthorizationTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function protectedRoutes(): array
    {
        return [
            'tokens.index' => ['GET', '/api/v1/tokens'],
            'tokens.store' => ['POST', '/api/v1/tokens'],
            'tokens.show' => ['GET', '/api/v1/tokens/1'],
            'tokens.update' => ['PATCH', '/api/v1/tokens/1'],
            'tokens.destroy' => ['DELETE', '/api/v1/tokens/1'],
            'tokens.info' => ['GET', '/api/v1/tokens/0xabc/info'],
            'tokens.top.holders' => ['GET', '/api/v1/tokens/0xabc/top-holders'],
            'transactions.index' => ['GET', '/api/v1/transactions'],
            'transactions.store' => ['POST', '/api/v1/transactions'],
            'transactions.show' => ['GET', '/api/v1/transactions/1'],
            'transactions.update' => ['PATCH', '/api/v1/transactions/1'],
            'transactions.destroy' => ['DELETE', '/api/v1/transactions/1'],
        ];
    }

    #[DataProvider('protectedRoutes')]
    public function test_it_rejects_an_unauthenticated_request(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertStatus(401);
    }

    /**
     * Regression test for a real-stack-only bug: $this->json() above forces
     * an `Accept: application/json` header, which makes
     * Illuminate\Http\Request::expectsJson() return true, which short-circuits
     * Illuminate\Auth\Middleware\Authenticate::unauthenticated() so it never
     * calls redirectTo() at all — meaning every case above passed even while
     * a plain client (curl with no Accept header, or any client that only
     * sends the browser-default `Accept: text/html,...`) hit a live 404
     * instead of a 401 on these same routes. With no Accept header forced
     * here, expectsJson() is false, so this exercises the exact path the
     * data-provider tests above cannot reach: redirectTo() runs,
     * historically called the framework's default
     * `redirectGuestsTo(fn () => route('login'))` and threw
     * RouteNotFoundException (this is an API-only app — routes/web.php has
     * no 'login' route), which bootstrap/app.php's renderable() maps to a
     * 404, masking the real 401. Fixed by overriding
     * `redirectGuestsTo(fn () => null)` in bootstrap/app.php so the redirect
     * target is always null regardless of expectsJson().
     */
    public function test_it_rejects_an_unauthenticated_request_without_a_json_accept_header(): void
    {
        $this->get('/api/v1/tokens', ['Accept' => 'text/html'])
            ->assertStatus(401);
    }
}
