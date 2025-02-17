<?php

namespace App\Http\Controllers\Tokens\DTOs;


readonly class TokenDTO
{
    /**
     * @param string $address
     * @param string $name
     * @param string $symbol
     * @param float $total_supply
     */
    public function __construct(
        public string $address,
        public string $name,
        public string $symbol,
        public float  $total_supply,
    )
    {
    }

    /**
     * @param array $data
     * @return TokenDTO
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            address: $data['tokenAddress'],
            name: $data['tokenName'],
            symbol: $data['tokenSymbol'],
            total_supply: $data['tokenTotalSupply'],
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'address' => $this->address,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'total_supply' => $this->total_supply,
        ];
    }
}