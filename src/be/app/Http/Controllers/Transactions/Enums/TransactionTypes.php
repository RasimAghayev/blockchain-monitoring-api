<?php

declare(strict_types=1);

namespace App\Http\Controllers\Transactions\Enums;

/**
 * The kind of a stored transaction.
 *
 * The transactions table holds the on-chain data of a transaction: hash, from
 * address, to address, amount and gas fee. A contract deployment has no
 * recipient, so the kind is derived from the stored columns instead of from a
 * column that is not part of the schema.
 */
enum TransactionTypes: int
{
    case TRANSFER = 1;

    case CONTRACT_CREATION = 2;

    /**
     * Derive the kind of a transaction from the recipient address it was stored with.
     */
    public static function forRecipient(?string $toAddress): self
    {
        return $toAddress === null || $toAddress === ''
            ? self::CONTRACT_CREATION
            : self::TRANSFER;
    }

    /**
     * Human readable name of the kind.
     */
    public function label(): string
    {
        return match ($this) {
            self::TRANSFER => 'Transfer',
            self::CONTRACT_CREATION => 'Contract creation',
        };
    }

    /**
     * The values accepted by the API.
     *
     * @return array<int, int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
