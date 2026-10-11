<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Http\Controllers\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Characterization tests for the Transactions feature.
 *
 * The feature does not run, at two independent levels:
 *
 *   1. App\Http\Controllers\Transactions\Enums\TransactionTypes does not exist and
 *      TransactionResource reads $this->name / $this->type, so the endpoints 500.
 *   2. The request layer validates name and type, which is the "Orders" domain, while
 *      the table holds hash, from_address, to_address, amount and gas_fee.
 *
 * The "numeric" cast on the model was a third break and is fixed; test_amount_and_gas_fee_cast_to_float
 * now pins the working cast.
 *
 * On top of that, Jobs\FetchTransactions::handle() is empty and
 * TransactionRepository::storeTokenTransactions() reads an undefined $this->address
 * and writes a timestamp column that does not exist.
 *
 * These tests pin the current behaviour so the break stays visible. When the feature
 * is rebuilt, replace them with tests for the intended contract.
 */
class TransactionReadTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/transactions';

    private function makeTransaction(string $hash = '0xhash1'): Transaction
    {
        return Transaction::create([
            'hash' => $hash,
            'from_address' => '0xfrom',
            'to_address' => '0xto',
            'amount' => 1.5,
            'gas_fee' => 0.01,
        ]);
    }

    public function test_amount_and_gas_fee_cast_to_float(): void
    {
        $transaction = $this->makeTransaction();

        $fresh = $transaction->fresh();

        $this->assertSame(1.5, $fresh->amount);
        $this->assertSame(0.01, $fresh->gas_fee);
    }

    public function test_listing_transactions_returns_an_error(): void
    {
        $this->makeTransaction();

        $this->assertSame(
            500,
            $this->getJson(self::ENDPOINT)->status(),
            'Endpoint recovered: rewrite this characterization test.'
        );
    }

    public function test_showing_a_transaction_returns_an_error(): void
    {
        $transaction = $this->makeTransaction();

        $this->assertSame(
            500,
            $this->getJson(self::ENDPOINT.'/'.$transaction->id)->status(),
            'Endpoint recovered: rewrite this characterization test.'
        );
    }

    public function test_the_table_matches_the_model_but_not_the_request_layer(): void
    {
        $columns = Transaction::query()
            ->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing('transactions');

        foreach (['hash', 'from_address', 'to_address', 'amount', 'gas_fee'] as $expected) {
            $this->assertContains($expected, $columns);
        }

        // The request layer validates name and type. Neither column exists.
        $this->assertNotContains('name', $columns);
        $this->assertNotContains('type', $columns);

        // A timestamp column is written by storeTokenTransactions() but never created.
        $this->assertNotContains('timestamp', $columns);

        $this->assertFileDoesNotExist(
            __DIR__.'/../../../App/Http/Controllers/Transactions/Enums/TransactionTypes.php'
        );
    }
}
