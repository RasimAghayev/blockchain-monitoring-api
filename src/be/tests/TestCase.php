<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Resolve a user for the `api` (JWT) guard without going through a real
     * login request. `actingAs($user, 'api')` sets the guard's resolved user
     * directly (Guard::setUser()), which php-open-source-saver/jwt-auth
     * supports the same as any other Illuminate guard — so feature tests for
     * the Tokens/Transactions endpoints can authenticate without parsing a
     * real signed JWT on every request. The real token issuance/parsing path
     * itself is covered separately by the AuthController feature tests.
     */
    protected function actingAsApiUser(?User $user = null): User
    {
        $user ??= User::factory()->create();

        $this->actingAs($user, 'api');

        return $user;
    }
}
