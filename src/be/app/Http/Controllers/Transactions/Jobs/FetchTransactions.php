<?php

namespace App\Http\Controllers\Transactions\Jobs;

use App\Http\Controllers\Transactions\Repositories\TransactionRepositoryInterface;
use App\Services\Blockchain\BlockchainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $address;

    public function __construct(string $address)
    {
        $this->address = $address;
    }

    /**
     * Fetch the latest transactions of the watched address and store them.
     *
     * The job is a no-op when no blockchain endpoint is configured, so a
     * deployment without an upstream API does not fail the queue worker.
     */
    public function handle(
        TransactionRepositoryInterface $transactionRepository,
        BlockchainService $blockchainService
    ): void {
        if (! $blockchainService->isConfigured()) {
            Log::info('Transaction fetch skipped: no blockchain endpoint is configured.');

            return;
        }

        $response = $blockchainService->getLatestTransactions($this->address);

        if ($response->status() !== 200) {
            Log::warning(sprintf(
                'Transaction fetch for %s returned status %d.',
                $this->address,
                $response->status()
            ));

            return;
        }

        $transactions = $response->getData(true)['transactions'] ?? [];
        $stored = $transactionRepository->storeTransactions($transactions);

        Log::info(sprintf('Stored %d transaction(s) for %s.', $stored, $this->address));
    }
}
