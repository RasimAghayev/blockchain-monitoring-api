<?php

namespace App\Http\Controllers\Transactions\Services;

use App\Http\Controllers\Transactions\DTOs\TransactionDTO;
use App\Http\Controllers\Transactions\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionServiceInterface
{
    public function getTransactions(Request $request,
        bool $includeTags,
        int $perPage): LengthAwarePaginator;

    public function createTransaction(TransactionDTO $transactionDTO): Transaction;

    public function getTransactionById(int $id): Transaction;

    public function updateTransaction(int $id, TransactionDTO $transactionDTO): Transaction;

    public function deleteTransaction(int $id): void;
}
