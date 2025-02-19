<?php

namespace App\Http\Controllers\Transactions\Repositories;

use App\Http\Controllers\Transactions\{DTOs\TransactionDTO, Models\Transaction};
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    /**
     * Get filtered transactions with pagination
     *
     * @param array $queryItems
     * @param bool $includeTags
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFilteredTransactions(array $queryItems,
                                         bool  $includeTags,
                                         int   $perPage): LengthAwarePaginator;

    /**
     * Create new transaction
     *
     * @param TransactionDTO $transactionDTO
     * @return Transaction
     */
    public function create(TransactionDTO $transactionDTO): Transaction;

    /**
     * Find transaction by ID
     *
     * @param int $id
     * @return Transaction|string
     */
    public function findOrFail(int $id): Transaction|string;

    /**
     * Update existing transaction
     *
     * @param int $id
     * @param TransactionDTO $transactionDTO
     * @return Transaction
     */
    public function update(int $id, TransactionDTO $transactionDTO): Transaction;

    /**
     * Delete transaction
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void;

}