<?php

declare(strict_types=1);

namespace App\Policies;

use App\Http\Controllers\Transactions\Models\Transaction;
use App\Models\User;

/**
 * Authorization for the Transactions resource.
 *
 * Same reasoning as TokenPolicy (see docs/adr/0001-flat-authorization.md): no
 * ownership/tenancy model exists for transactions either — they belong to the
 * watched addresses, not to the authenticated caller. Every ability is
 * intentionally `true` for any authenticated user.
 */
class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function createAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return true;
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return true;
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return true;
    }
}
