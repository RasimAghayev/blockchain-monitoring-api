<?php

namespace App\Http\Controllers\Transactions\DTOs;


readonly class TransactionDTO
{
    /**
     * @param string $hash
     * @param string $from_address
     * @param string $to_address
     * @param float  $amount
     */
    public function __construct(
        public string $hash,
        public string $from_address,
        public string $to_address,
        public float  $amount,
    )
    {
    }

    /**
     * @param array $data
     * @return TransactionDTO
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            hash: $data['transactionHash'],
            from_address: $data['transactionFromAddress'],
            to_address: $data['transactionToAddress'],
            amount: $data['transactionAmount'] ?? 0,
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'from_address' => $this->from_address,
            'to_address' => $this->to_address,
            'amount' => $this->amount,
        ];
    }
}