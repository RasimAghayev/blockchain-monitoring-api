<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Http\Controllers\Transactions\Jobs\FetchTransactions;
use App\Http\Controllers\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TransactionFetchTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = '0xdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef';

    public function test_it_stores_the_transactions_of_the_watched_address(): void
    {
        Http::fake([
            '*/address/*/transactions*' => Http::response([
                'transactions' => [
                    [
                        'hash' => '0xabc',
                        'from' => '0x1111111111111111111111111111111111111111',
                        'to' => '0x2222222222222222222222222222222222222222',
                        'amount' => 1.25,
                        'gas_fee' => 0.0004,
                    ],
                ],
            ]),
        ]);

        dispatch(new FetchTransactions(self::ADDRESS));

        $this->assertDatabaseHas('transactions', [
            'hash' => '0xabc',
            'from_address' => '0x1111111111111111111111111111111111111111',
            'to_address' => '0x2222222222222222222222222222222222222222',
            'amount' => 1.25,
            'gas_fee' => 0.0004,
        ]);
    }

    public function test_it_stores_a_contract_creation_without_a_recipient(): void
    {
        Http::fake([
            '*/address/*/transactions*' => Http::response([
                'transactions' => [
                    [
                        'hash' => '0xdef',
                        'from' => '0x1111111111111111111111111111111111111111',
                        'to' => null,
                        'amount' => 0,
                        'gas_fee' => 0.1,
                    ],
                ],
            ]),
        ]);

        dispatch(new FetchTransactions(self::ADDRESS));

        $this->assertDatabaseHas('transactions', [
            'hash' => '0xdef',
            'to_address' => '',
            'amount' => 0,
        ]);
    }

    public function test_it_updates_a_transaction_that_is_seen_again(): void
    {
        Transaction::create([
            'hash' => '0xabc',
            'from_address' => '0x1111111111111111111111111111111111111111',
            'to_address' => '0x2222222222222222222222222222222222222222',
            'amount' => 1,
            'gas_fee' => 0.1,
        ]);

        Http::fake([
            '*/address/*/transactions*' => Http::response([
                'transactions' => [
                    [
                        'hash' => '0xabc',
                        'from' => '0x1111111111111111111111111111111111111111',
                        'to' => '0x2222222222222222222222222222222222222222',
                        'amount' => 2.5,
                        'gas_fee' => 0.2,
                    ],
                ],
            ]),
        ]);

        dispatch(new FetchTransactions(self::ADDRESS));

        $this->assertSame(1, Transaction::query()->count());
        $this->assertDatabaseHas('transactions', [
            'hash' => '0xabc',
            'amount' => 2.5,
            'gas_fee' => 0.2,
        ]);
    }

    public function test_it_does_nothing_when_no_blockchain_endpoint_is_configured(): void
    {
        config(['services.blockchain.base_url' => '']);
        Http::fake();

        dispatch(new FetchTransactions(self::ADDRESS));

        Http::assertNothingSent();
        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_it_does_not_store_anything_when_the_upstream_api_fails(): void
    {
        Http::fake([
            '*/address/*/transactions*' => Http::response(['error' => 'not found'], 404),
        ]);

        dispatch(new FetchTransactions(self::ADDRESS));

        $this->assertSame(0, Transaction::query()->count());
    }
}
