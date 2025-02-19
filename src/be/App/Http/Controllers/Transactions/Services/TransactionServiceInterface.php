<?php

namespace App\Http\Controllers\Transactions\Services;

use App\Http\Controllers\Transactions\DTOs\TransactionDTO;
use App\Http\Controllers\Transactions\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionServiceInterface
{
    /**
     * @param Request $request
     * @param bool $includeTags
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTransactions(Request $request,
                                 bool    $includeTags,
                                 int     $perPage): LengthAwarePaginator;

    /**
     * @param TransactionDTO $transactionDTO
     * @return Transaction
     */
    public function createTransaction(TransactionDTO $transactionDTO): Transaction;

    /**
     * @param int $id
     * @return Transaction|string
     */
    public function getTransactionById(int $id): Transaction|string;

    /**
     * @param int $id
     * @param TransactionDTO $transactionDTO
     * @return Transaction
     */
    public function updateTransaction(int $id, TransactionDTO $transactionDTO): Transaction;

    /**
     * @param int $id
     * @return void
     */
    public function deleteTransaction(int $id): void;

}