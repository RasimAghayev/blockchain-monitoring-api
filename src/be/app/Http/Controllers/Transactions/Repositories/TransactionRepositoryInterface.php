<?php

namespace App\Http\Controllers\Transactions\Repositories;

use App\Http\Controllers\Transactions\DTOs\TransactionDTO;
use App\Http\Controllers\Transactions\Models\Transaction;
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
     */
    public function findOrFail(int $id): Transaction|string;

    /**
     * Update existing transaction
     */
    public function update(int $id, TransactionDTO $transactionDTO): Transaction;

    /**
     * Delete transaction
     */
    public function delete(int $id): void;
}
