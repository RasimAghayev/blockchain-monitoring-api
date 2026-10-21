<?php

declare(strict_types=1);

namespace App\Http\Controllers\Transactions\Traits;

trait TransactionRequestTrait
{
    /**
     * Validation rules for the columns the transactions table stores.
     *
     * @return array<string, mixed>
     */
    protected function getCommonRules(): array
    {
        return [
            'transactionHash' => ['string', 'min:3', 'max:255'],
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
