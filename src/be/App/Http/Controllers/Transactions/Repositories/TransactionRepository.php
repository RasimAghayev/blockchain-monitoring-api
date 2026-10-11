<?php

namespace App\Http\Controllers\Transactions\Repositories;

use App\Http\Controllers\Transactions\DTOs\TransactionDTO;
use App\Http\Controllers\Transactions\Models\Transaction;
use App\Services\Blockchain\BlockchainService;
use App\Services\Telegram\TelegramService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(
        protected Transaction $transactionModel,
        protected TelegramService $telegramService,
        protected BlockchainService $blockchainService
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
    public function findOrFail(int $id): Transaction|string
    {
        $transaction = $this->transactionModel->find($id);

        if (! $transaction) {
            return "Transaction not found with ID: {$id}";
        }

        return $transaction;
    }

    /**
     * Delete transaction
     */
    public function delete(int $id): void
    {
        $transaction = $this->findOrFail($id);
        $transaction->delete();
    }

    public function storeTokenTransactions(): void
    {
        $latestTransactions = $this->blockchainService->getLatestTransactions($this->address);

        foreach ($latestTransactions as $tx) {
            Transaction::updateOrCreate(
                ['hash' => $tx['hash']],
                [
                    'from_address' => $tx['from'],
                    'to_address' => $tx['to'],
                    'amount' => $tx['amount'],
                    'gas_fee' => $tx['gas_fee'] ?? 0,
                    'timestamp' => $tx['timestamp'],
                ]
            );

            $message = $this->prepareTransactionMessage($tx);

            $this->telegramService->sendMessage($message);
        }
    }

    private function prepareTransactionMessage(array $tx): string
    {
        return "🔔 *New Transaction Detected!* 🔔\n\n".
            "🔹 *Hash:* [{$tx['hash']}](https://etherscan.io/tx/{$tx['hash']})\n".
            "📍 *From:* `{$tx['from']}`\n".
            "🎯 *To:* `{$tx['to']}`\n".
            "💰 *Amount:* `{$tx['amount']}` ETH\n".
            "⛽ *Gas Fee:* `{$tx['gas_fee']}` Gwei\n".
            '🕒 *Time:* `'.date('Y-m-d H:i:s', $tx['timestamp'])."`\n\n".
            '#Blockchain #Crypto';
    }
}
