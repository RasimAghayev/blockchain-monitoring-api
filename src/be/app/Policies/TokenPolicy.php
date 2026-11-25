<?php

declare(strict_types=1);

namespace App\Policies;

use App\Http\Controllers\Tokens\Models\Token;
use App\Models\User;

/**
 * Authorization for the Tokens resource.
 *
 * This domain has no ownership/tenancy model (see docs/adr/0001-flat-authorization.md):
 * every token is global, shared state (the set of addresses this deployment watches),
 * not owned by a particular user. There is also no role/permission system in the
 * domain today (a single `User` model, no roles table). The only real security
 * boundary that exists is "has a valid credential for this API" vs. "does not" — once
 * authenticated, every user may manage every token. Every ability below is
 * intentionally `true` for any authenticated user; this is a deliberate, documented
 * decision, not a stub.
 */
class TokenPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function createAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Token $token): bool
    {
        return true;
    }

    public function update(User $user, Token $token): bool
    {
        return true;
    }

    public function delete(User $user, Token $token): bool
    {
        return true;
    }
}
