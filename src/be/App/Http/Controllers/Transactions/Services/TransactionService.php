<?php
declare(strict_types=1);

namespace App\Http\Controllers\Transactions\Services;

use App\Http\Controllers\Transactions\{DTOs\TransactionDTO,
    Filters\TransactionFilters,
    Models\Transaction,
    Repositories\TransactionRepositoryInterface};
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionService implements TransactionServiceInterface
{
    /**
     * @param TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    )
    {
    }

    /**
     * Get filtered transactions with pagination
     *
     * @param Request $request
     * @param bool $includeTags
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTransactions(Request $request, bool $includeTags, int $perPage): LengthAwarePaginator
    {
        $filter = new TransactionFilters();
        $queryItems = $filter->transform($request);

        return $this->transactionRepository->getFilteredTransactions(
            queryItems: $queryItems,
            includeTags: $includeTags,
            perPage: $perPage
        );
    }

    /**
     * @param TransactionDTO $transactionDTO
     * @return Transaction
     */
    public function createTransaction(TransactionDTO $transactionDTO): Transaction
    {
        return DB::transaction(function () use ($transactionDTO) {
            return $this->transactionRepository->create($transactionDTO);
        });
    }

    /**
     * @param int $id
     * @return Transaction|string
     */
    public function getTransactionById(int $id): Transaction|string
    {
        return $this->transactionRepository->findOrFail($id);
    }

    /**
     * @param int $id
     * @param TransactionDTO $transactionDTO
     * @return Transaction
     */
    public function updateTransaction(int $id, TransactionDTO $transactionDTO): Transaction
    {
        return DB::transaction(function () use ($id, $transactionDTO) {
            return $this->transactionRepository->update($id, $transactionDTO);
        });
    }

    /**
     * @param int $id
     * @return void
     */
    public function deleteTransaction(int $id): void
    {
        DB::transaction(function () use ($id) {
            $this->transactionRepository->delete($id);
        });
    }

}