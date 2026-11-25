<?php

declare(strict_types=1);

namespace App\Http\Controllers\Transactions\Traits;

use Illuminate\Validation\Rule;

trait TransactionRequestTrait
{
    /**
     * Validation rules for the columns the transactions table stores.
     *
     * $ignoreTransactionId excludes the current row from the uniqueness check
     * on update (same pattern as TokenRequestTrait::getCommonRules()): without
     * it, a transaction being saved with its own unchanged hash would fail
     * validation against itself.
     *
     * @return array<string, mixed>
     */
    protected function getCommonRules(?int $ignoreTransactionId = null): array
    {
        return [
            'transactionHash' => [
                'string',
                'min:3',
                'max:255',
                Rule::unique('transactions', 'hash')->ignore($ignoreTransactionId),
            ],
            'transactionFromAddress' => ['string', 'min:3', 'max:255'],
            'transactionToAddress' => ['string', 'max:255'],
            'transactionAmount' => ['numeric', 'min:0'],
            'transactionGasFee' => ['numeric', 'min:0'],
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
            'transactionHash.required' => 'Please enter the transaction hash.',
            'transactionHash.min' => 'The hash must be at least 3 characters long.',
            'transactionHash.unique' => 'A transaction with this hash already exists.',
            'transactionFromAddress.required' => 'Please enter the sender address.',
            'transactionFromAddress.min' => 'The sender address must be at least 3 characters long.',
            'transactionToAddress.string' => 'The recipient address must be a string.',
            'transactionAmount.required' => 'Please enter the amount.',
            'transactionAmount.numeric' => 'The amount must be a numeric value.',
            'transactionGasFee.required' => 'Please enter the gas fee.',
            'transactionGasFee.numeric' => 'The gas fee must be a numeric value.',
        ];
    }

    /**
     * The fields a transaction cannot be stored without.
     *
     * A contract deployment has no recipient, so the recipient address is not
     * part of this list.
     *
     * @return array<int, string>
     */
    protected function getRequiredFields(): array
    {
        return [
            'transactionHash',
            'transactionFromAddress',
            'transactionAmount',
            'transactionGasFee',
        ];
    }
}
