<?php
declare(strict_types=1);

namespace App\Http\Controllers\Tokens\Traits;

trait TokenRequestTrait
{
    /**
     * Get common validation rules
     *
     * @return array<string, mixed>
     */
    protected function getCommonRules(): array
    {
        return [
            'tokenAddress' => ['string', 'min:3', 'max:255'],
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
            'tokenTotalSupply.numeric' => 'Total supply must be a numeric value.'
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