<?php

namespace App\Console\Commands;

use App\Http\Controllers\Transactions\Jobs\FetchTransactions;
use Illuminate\Console\Command;

class FetchTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:fetch {address : The address to fetch the transactions of}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the latest transactions of an address and store them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        FetchTransactions::dispatch((string) $this->argument('address'));

        $this->info('Transaction fetch queued for '.$this->argument('address').'.');

        return self::SUCCESS;
    }
}
