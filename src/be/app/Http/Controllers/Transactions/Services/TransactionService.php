<?php

declare(strict_types=1);

namespace App\Http\Controllers\Transactions\Services;

use App\Http\Controllers\Transactions\DTOs\TransactionDTO;
use App\Http\Controllers\Transactions\Filters\TransactionFilters;
use App\Http\Controllers\Transactions\Models\Transaction;
use App\Http\Controllers\Transactions\Repositories\TransactionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {}

    /**
     * Get filtered transactions with pagination
     */
    public function getTransactions(Request $request, bool $includeTags, int $perPage): LengthAwarePaginator
    {
        $filter = new TransactionFilters;
        $queryItems = $filter->transform($request);

        return $this->transactionRepository->getFilteredTransactions(
            queryItems: $queryItems,
            includeTags: $includeTags,
            perPage: $perPage
        );
    }

    public function createTransaction(TransactionDTO $transactionDTO): Transaction
    {
        return DB::transaction(function () use ($transactionDTO) {
            return $this->transactionRepository->create($transactionDTO);
        });
    }

    public function getTransactionById(int $id): Transaction
    {
        return $this->transactionRepository->findOrFail($id);
    }

    public function updateTransaction(int $id, TransactionDTO $transactionDTO): Transaction
    {
        return DB::transaction(function () use ($id, $transactionDTO) {
            return $this->transactionRepository->update($id, $transactionDTO);
        });
    }

    public function deleteTransaction(int $id): void
    {
        DB::transaction(function () use ($id) {
            $this->transactionRepository->delete($id);
        });
    }
}
