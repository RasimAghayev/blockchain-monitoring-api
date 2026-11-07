<?php

namespace App\Http\Controllers\Transactions\Repositories;

use App\Http\Controllers\Transactions\DTOs\TransactionDTO;
use App\Http\Controllers\Transactions\Models\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    /**
     * Get filtered transactions with pagination
     */
    public function getFilteredTransactions(array $queryItems,
        bool $includeTags,
        int $perPage): LengthAwarePaginator;

    /**
     * Create new transaction
     */
    public function create(TransactionDTO $transactionDTO): Transaction;

    /**
     * Find transaction by ID
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Transaction;

    /**
     * Update existing transaction
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, TransactionDTO $transactionDTO): Transaction;

    /**
     * Delete transaction
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $id): void;

    /**
     * Store transactions fetched from the chain
     *
     * @param  array<int, array<string, mixed>>  $transactions
     */
    public function storeTransactions(array $transactions): int;
}
