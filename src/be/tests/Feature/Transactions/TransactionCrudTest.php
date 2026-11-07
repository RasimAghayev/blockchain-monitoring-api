<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Http\Controllers\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCrudTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/transactions';

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'transactionHash' => '0x9f2c1b7c9d4e5f60718293a4b5c6d7e8f9012345',
            'transactionFromAddress' => '0x1111111111111111111111111111111111111111',
            'transactionToAddress' => '0x2222222222222222222222222222222222222222',
            'transactionAmount' => 1.5,
            'transactionGasFee' => 0.0004,
        ], $overrides);
    }

    public function test_it_creates_a_transaction(): void
    {
        $response = $this->postJson(self::ENDPOINT, $this->payload());

        $response->assertCreated()
            ->assertJsonPath('error', null)
            ->assertJsonPath('result.message', 'Transaction successfully created')
            ->assertJsonPath('result.data.transactionHash', '0x9f2c1b7c9d4e5f60718293a4b5c6d7e8f9012345')
            ->assertJsonPath('result.data.transactionFromAddress', '0x1111111111111111111111111111111111111111')
            ->assertJsonPath('result.data.transactionToAddress', '0x2222222222222222222222222222222222222222')
            ->assertJsonPath('result.data.transactionAmount', 1.5)
            ->assertJsonPath('result.data.transactionGasFee', 0.0004)
            ->assertJsonPath('result.data.type.value', 1)
            ->assertJsonPath('result.data.type.label', 'Transfer');

        $this->assertDatabaseHas('transactions', [
            'hash' => '0x9f2c1b7c9d4e5f60718293a4b5c6d7e8f9012345',
            'from_address' => '0x1111111111111111111111111111111111111111',
            'to_address' => '0x2222222222222222222222222222222222222222',
            'amount' => 1.5,
            'gas_fee' => 0.0004,
        ]);
    }

    public function test_it_marks_a_transaction_without_a_recipient_as_a_contract_creation(): void
    {
        $payload = $this->payload();
        unset($payload['transactionToAddress']);

        $this->postJson(self::ENDPOINT, $payload)
            ->assertCreated()
            ->assertJsonPath('result.data.transactionToAddress', '')
            ->assertJsonPath('result.data.type.value', 2)
            ->assertJsonPath('result.data.type.label', 'Contract creation');

        $this->assertDatabaseHas('transactions', [
            'hash' => '0x9f2c1b7c9d4e5f60718293a4b5c6d7e8f9012345',
            'to_address' => '',
        ]);
    }

    public function test_it_rejects_a_transaction_without_the_required_fields(): void
    {
        $payload = $this->payload();
        unset($payload['transactionHash'], $payload['transactionAmount']);

        $this->postJson(self::ENDPOINT, $payload)
            ->assertStatus(422)
            ->assertJsonPath('error.transactionHash.0', 'Please enter the transaction hash.')
            ->assertJsonPath('error.transactionAmount.0', 'Please enter the amount.');

        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_it_rejects_a_duplicate_hash(): void
    {
        Transaction::create([
            'hash' => '0x9f2c1b7c9d4e5f60718293a4b5c6d7e8f9012345',
            'from_address' => '0x1111111111111111111111111111111111111111',
            'to_address' => '0x2222222222222222222222222222222222222222',
            'amount' => 1,
            'gas_fee' => 0.1,
        ]);

        $this->postJson(self::ENDPOINT, $this->payload())->assertStatus(500);

        $this->assertSame(1, Transaction::query()->count());
    }

    public function test_it_lists_transactions(): void
    {
        $this->createTransaction('0xaaaabbbbccccddddeeeeffff0000111122223333');

        $this->getJson(self::ENDPOINT)
            ->assertOk()
            ->assertJsonPath('error', null)
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.transactionHash', '0xaaaabbbbccccddddeeeeffff0000111122223333');
    }

    public function test_it_filters_transactions_by_hash(): void
    {
        $this->createTransaction('0xaaaabbbbccccddddeeeeffff0000111122223333');
        $this->createTransaction('0x9999888877776666555544443333222211110000');

        $this->getJson(self::ENDPOINT.'?transactionHash[eq]=0x9999888877776666555544443333222211110000')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.transactionHash', '0x9999888877776666555544443333222211110000');
    }

    public function test_it_shows_a_transaction(): void
    {
        $transaction = $this->createTransaction('0xaaaabbbbccccddddeeeeffff0000111122223333');

        $this->getJson(self::ENDPOINT.'/'.$transaction->id)
            ->assertOk()
            ->assertJsonPath('result.message', 'Transaction successfully show: '.$transaction->id)
            ->assertJsonPath('result.data.transactionHash', '0xaaaabbbbccccddddeeeeffff0000111122223333');
    }

    public function test_it_returns_404_for_a_missing_transaction(): void
    {
        $this->getJson(self::ENDPOINT.'/999999')
            ->assertNotFound()
            ->assertJsonPath('error', 'Resource not found: Transaction not found with ID: 999999');
    }

    public function test_it_updates_a_transaction_with_a_patch(): void
    {
        $transaction = $this->createTransaction('0xaaaabbbbccccddddeeeeffff0000111122223333');

        $this->patchJson(self::ENDPOINT.'/'.$transaction->id, [
            'transactionAmount' => 9.5,
        ])
            ->assertOk()
            ->assertJsonPath('result.message', 'Transaction successfully updated')
            ->assertJsonPath('result.data.transactionAmount', 9.5);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'hash' => '0xaaaabbbbccccddddeeeeffff0000111122223333',
            'amount' => 9.5,
        ]);
    }

    public function test_it_replaces_a_transaction_with_a_put(): void
    {
        $transaction = $this->createTransaction('0xaaaabbbbccccddddeeeeffff0000111122223333');

        $this->putJson(self::ENDPOINT.'/'.$transaction->id, $this->payload([
            'transactionHash' => '0x1111222233334444555566667777888899990000',
            'transactionAmount' => 3.25,
        ]))
            ->assertOk()
            ->assertJsonPath('result.data.transactionHash', '0x1111222233334444555566667777888899990000')
            ->assertJsonPath('result.data.transactionAmount', 3.25);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'hash' => '0x1111222233334444555566667777888899990000',
            'amount' => 3.25,
        ]);
    }

    public function test_it_rejects_a_put_without_the_required_fields(): void
    {
        $transaction = $this->createTransaction('0xaaaabbbbccccddddeeeeffff0000111122223333');

        $this->putJson(self::ENDPOINT.'/'.$transaction->id, [])
            ->assertStatus(422)
            ->assertJsonPath('error.transactionHash.0', 'Please enter the transaction hash.');
    }

    public function test_it_deletes_a_transaction(): void
    {
        $transaction = $this->createTransaction('0xaaaabbbbccccddddeeeeffff0000111122223333');

        $this->deleteJson(self::ENDPOINT.'/'.$transaction->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_it_returns_404_when_deleting_a_missing_transaction(): void
    {
        $this->deleteJson(self::ENDPOINT.'/999999')
            ->assertNotFound()
            ->assertJsonPath('error', 'Resource not found: Transaction not found with ID: 999999');
    }

    private function createTransaction(string $hash): Transaction
    {
        return Transaction::create([
            'hash' => $hash,
            'from_address' => '0x1111111111111111111111111111111111111111',
            'to_address' => '0x2222222222222222222222222222222222222222',
            'amount' => 1,
            'gas_fee' => 0.1,
        ]);
    }
}
