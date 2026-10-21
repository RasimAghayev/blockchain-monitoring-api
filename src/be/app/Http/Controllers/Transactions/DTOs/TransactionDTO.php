<?php

namespace App\Http\Controllers\Transactions\DTOs;

readonly class TransactionDTO
{
    public function __construct(
        public string $hash,
        public string $from_address,
        public string $to_address,
        public float $amount,
        public float $gas_fee,
    ) {}

    /**
     * Build the DTO from a validated request.
     *
     * The API speaks camelCase while the table stores snake_case, so both
     * spellings are accepted. The camelCase key wins: on a partial update the
     * model values are merged in as a fallback and the submitted values have to
     * take precedence.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            hash: (string) ($data['transactionHash'] ?? $data['hash'] ?? ''),
            from_address: (string) ($data['transactionFromAddress'] ?? $data['from_address'] ?? ''),
            to_address: (string) ($data['transactionToAddress'] ?? $data['to_address'] ?? ''),
            amount: (float) ($data['transactionAmount'] ?? $data['amount'] ?? 0),
            gas_fee: (float) ($data['transactionGasFee'] ?? $data['gas_fee'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'from_address' => $this->from_address,
            'to_address' => $this->to_address,
            'amount' => $this->amount,
            'gas_fee' => $this->gas_fee,
        ];
    }
}
