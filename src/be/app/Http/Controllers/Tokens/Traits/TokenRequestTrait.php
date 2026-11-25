<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tokens\Traits;

use Illuminate\Validation\Rule;

trait TokenRequestTrait
{
    /**
     * Get common validation rules.
     *
     * `tokenAddress` carries a `unique:tokens,address` check (docs/REPOSITORY_AUDIT.md
     * P0-3): without it, a duplicate address reaches the database's own unique
     * constraint unvalidated and throws an uncaught QueryException. Update requests
     * pass the current token's id so the row is excluded from its own uniqueness
     * check (a PUT/PATCH that resends the same address must not reject itself).
     *
     * @return array<string, mixed>
     */
    protected function getCommonRules(?int $ignoreTokenId = null): array
    {
        return [
            'tokenAddress' => [
                'string', 'min:3', 'max:255',
                Rule::unique('tokens', 'address')->ignore($ignoreTokenId),
            ],
            'tokenName' => ['string', 'min:3', 'max:255'],
            'tokenSymbol' => ['string', 'min:3', 'max:255'],
            'tokenTotalSupply' => ['numeric', 'min:1'],
        ];
    }

    /**
     * Get common validation messages
     *
     * @return array<string, string>
     */
    protected function getCommonMessages(): array
    {
        return [
            'tokenAddress.required' => 'Please enter the address.',
            'tokenAddress.unique' => 'This address is already in use.',
            'tokenName.required' => 'Please enter the name.',
            'tokenName.min' => 'The name must be at least 3 characters long.',
            'tokenSymbol.required' => 'Please enter the symbol.',
            'tokenTotalSupply.required' => 'Please enter the total supply.',
            'tokenTotalSupply.numeric' => 'Total supply must be a numeric value.',
        ];
    }

    /**
     * We convert the camelCase format from the frontend to snake_case format
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'address' => $this->tokenAddress,
            'name' => $this->tokenName,
            'symbol' => $this->tokenSymbol,
            'total_supply' => $this->tokenTotalSupply,
        ]);
    }
}
