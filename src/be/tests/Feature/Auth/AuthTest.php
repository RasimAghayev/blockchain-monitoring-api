<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Covers the AuthController added to make re-enabling auth:api possible at
 * all (docs/REPOSITORY_AUDIT.md P0-1) — login, me, refresh, logout, and the
 * 401 paths a caller with no/bad/expired credentials hits on every protected
 * route, not just these.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_ENDPOINT = '/api/v1/auth/login';

    private const ME_ENDPOINT = '/api/v1/auth/me';

    private const REFRESH_ENDPOINT = '/api/v1/auth/refresh';

    private const LOGOUT_ENDPOINT = '/api/v1/auth/logout';

    private function createUser(string $password = 'correct-password'): User
    {
        return User::factory()->create([
            'password' => Hash::make($password),
        ]);
    }

    public function test_it_logs_in_with_valid_credentials(): void
    {
        $user = $this->createUser('correct-password');

        $response = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('error', null)
            ->assertJsonPath('result.token_type', 'bearer');

        $this->assertNotEmpty($response->json('result.access_token'));
        $this->assertIsInt($response->json('result.expires_in'));
        $this->assertGreaterThan(0, $response->json('result.expires_in'));
    }

    public function test_it_rejects_an_unknown_email(): void
    {
        $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ])
            ->assertStatus(401)
            ->assertJsonPath('error', 'Invalid credentials.');
    }

    public function test_it_rejects_the_wrong_password(): void
    {
        $user = $this->createUser('correct-password');

        $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertStatus(401)
            ->assertJsonPath('error', 'Invalid credentials.');
    }

    public function test_it_requires_email_and_password(): void
    {
        $response = $this->postJson(self::LOGIN_ENDPOINT, []);

        $response->assertStatus(422);
        $errors = $response->json('error') ?? [];
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = $this->createUser();
        $this->actingAsApiUser($user);

        $this->getJson(self::ME_ENDPOINT)
            ->assertOk()
            ->assertJsonPath('result.id', $user->id)
            ->assertJsonPath('result.email', $user->email);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson(self::ME_ENDPOINT)->assertStatus(401);
    }

    public function test_it_refreshes_a_valid_token_for_a_new_one(): void
    {
        $user = $this->createUser('correct-password');
        $token = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->json('result.access_token');

        $response = $this->withToken($token)->postJson(self::REFRESH_ENDPOINT);

        $response->assertOk()->assertJsonPath('result.token_type', 'bearer');
        $this->assertNotEmpty($response->json('result.access_token'));
        // php-open-source-saver/jwt-auth always mints a new signed string on
        // refresh, even for the same subject + near-identical claims.
        $this->assertNotSame($token, $response->json('result.access_token'));
    }

    public function test_refresh_requires_authentication(): void
    {
        $this->postJson(self::REFRESH_ENDPOINT)->assertStatus(401);
    }

    public function test_it_logs_out_and_blacklists_the_token(): void
    {
        $user = $this->createUser('correct-password');
        $token = $this->postJson(self::LOGIN_ENDPOINT, [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->json('result.access_token');

        $this->withToken($token)->postJson(self::LOGOUT_ENDPOINT)
            ->assertOk()
            ->assertJsonPath('result.message', 'Successfully logged out.');

        // JWT_BLACKLIST_ENABLED defaults to true (config/jwt.php) — the same
        // token must not be usable again after logout.
        $this->withToken($token)->getJson(self::ME_ENDPOINT)->assertStatus(401);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson(self::LOGOUT_ENDPOINT)->assertStatus(401);
    }
}
