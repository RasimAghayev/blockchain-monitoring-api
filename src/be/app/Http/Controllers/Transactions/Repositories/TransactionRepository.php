<?php

namespace App\Http\Controllers\Transactions\Repositories;

use App\Http\Controllers\Transactions\DTOs\TransactionDTO;
use App\Http\Controllers\Transactions\Models\Transaction;
use App\Services\Telegram\TelegramService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(
        protected Transaction $transactionModel,
        protected TelegramService $telegramService
    ) {}

    /**
     * Get filtered transactions with pagination
     */
    public function getFilteredTransactions(array $queryItems,
        bool $includeTags,
        int $perPage): LengthAwarePaginator
    {
        $query = $this->transactionModel->where($queryItems);

        $maxPerPage = 100;
        $perPage = min($perPage, $maxPerPage);

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    /**
     * Create new transaction
     */
    public function create(TransactionDTO $transactionDTO): Transaction
    {
        return $this->transactionModel->create($transactionDTO->toArray());
    }

    /**
     * Update existing transaction
     */
    public function update(int $id, TransactionDTO $transactionDTO): Transaction
    {
        $transaction = $this->findOrFail($id);
        $transaction->update($transactionDTO->toArray());

        return $transaction->fresh();
    }

    /**
     * Find transaction by ID
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Transaction
    {
        $transaction = $this->transactionModel->find($id);

        if (! $transaction) {
            throw new ModelNotFoundException("Transaction not found with ID: {$id}");
        }

        return $transaction;
    }

    /**
     * Delete transaction
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $id): void
    {
        $this->findOrFail($id)->delete();
    }

    /**
     * Store transactions fetched from the chain.
     *
     * Rows are matched on the transaction hash, so a transaction that is seen
     * twice updates the row that already exists instead of failing on the
     * unique index.
     *
     * @param  array<int, array<string, mixed>>  $transactions
     */
    public function storeTransactions(array $transactions): int
    {
        $stored = 0;

        foreach ($transactions as $transaction) {
            $attributes = $this->toAttributes($transaction);

            if ($attributes === null) {
                continue;
            }

            $model = Transaction::updateOrCreate(['hash' => $attributes['hash']], $attributes);
            $stored++;

            if ($model->wasRecentlyCreated) {
                $this->notifyAbout($model);
            }
        }

        return $stored;
    }

    /**
     * Map an upstream transaction onto the columns of the transactions table.
     *
     * The upstream payload is keyed by hash, from, to, amount and gas fee. A
     * transaction without a recipient is a contract deployment and is stored
     * with an empty recipient address.
     *
     * @param  array<string, mixed>  $transaction
     * @return array<string, mixed>|null
     */
    private function toAttributes(array $transaction): ?array
    {
        $hash = $transaction['hash'] ?? $transaction['transactionHash'] ?? null;

        if (! is_string($hash) || $hash === '') {
            return null;
        }

        return [
            'hash' => $hash,
            'from_address' => (string) ($transaction['from'] ?? $transaction['from_address'] ?? ''),
            'to_address' => (string) ($transaction['to'] ?? $transaction['to_address'] ?? ''),
            'amount' => (float) ($transaction['amount'] ?? $transaction['value'] ?? 0),
            'gas_fee' => (float) ($transaction['gas_fee'] ?? $transaction['gasFee'] ?? 0),
        ];
    }

    /**
     * Send the new transaction to the configured Telegram channel.
     */
    private function notifyAbout(Transaction $transaction): void
    {
        if (! $this->telegramService->isConfigured()) {
            return;
        }

        try {
            $this->telegramService->sendMessage($this->prepareTransactionMessage($transaction));
        } catch (Throwable $e) {
            Log::warning('Could not send the transaction notification: '.$e->getMessage());
        }
    }

    private function prepareTransactionMessage(Transaction $transaction): string
    {
        return "🔔 *New Transaction Detected!* 🔔\n\n".
            "🔹 *Hash:* [{$transaction->hash}](https://etherscan.io/tx/{$transaction->hash})\n".
            "📍 *From:* `{$transaction->from_address}`\n".
            "🎯 *To:* `{$transaction->to_address}`\n".
            "💰 *Amount:* `{$transaction->amount}` ETH\n".
            "⛽ *Gas Fee:* `{$transaction->gas_fee}` Gwei\n".
            '🕒 *Time:* `'.$transaction->created_at?->format('Y-m-d H:i:s')."`\n\n".
            '#Blockchain #Crypto';
    }
}
