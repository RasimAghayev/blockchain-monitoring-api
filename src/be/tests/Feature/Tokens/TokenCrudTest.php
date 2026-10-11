<?php

declare(strict_types=1);

namespace Tests\Feature\Tokens;

use App\Http\Controllers\Tokens\Models\Token;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenCrudTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/tokens';

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'tokenAddress' => '0x1234567890abcdef1234567890abcdef12345678',
            'tokenName' => 'MyToken',
            'tokenSymbol' => 'MTK',
            'tokenTotalSupply' => 1000000.5,
        ], $overrides);
    }

    public function test_it_creates_a_token(): void
    {
        $response = $this->postJson(self::ENDPOINT, $this->payload());

        $response->assertCreated()
            ->assertJsonPath('error', null)
            ->assertJsonPath('result.message', 'Token successfully created')
            ->assertJsonPath('result.data.tokenAddress', '0x1234567890abcdef1234567890abcdef12345678')
            ->assertJsonPath('result.data.tokenName', 'MyToken')
            ->assertJsonPath('result.data.tokenSymbol', 'MTK');

        $this->assertDatabaseHas('tokens', [
            'address' => '0x1234567890abcdef1234567890abcdef12345678',
            'name' => 'MyToken',
            'symbol' => 'MTK',
        ]);
    }

    public function test_it_rejects_a_duplicate_address(): void
    {
        Token::create([
            'address' => '0x1234567890abcdef1234567890abcdef12345678',
            'name' => 'Existing',
            'symbol' => 'EX',
            'total_supply' => 10,
        ]);

        $this->postJson(self::ENDPOINT, $this->payload())
            ->assertStatus(500);

        $this->assertSame(1, Token::query()->count());
    }

    public function test_it_lists_tokens(): void
    {
        Token::create([
            'address' => '0xaaaabbbbccccddddeeeeffff0000111122223333',
            'name' => 'Listed',
            'symbol' => 'LST',
            'total_supply' => 42,
        ]);

        $this->getJson(self::ENDPOINT)
            ->assertOk()
            ->assertJsonPath('error', null)
            ->assertJsonPath('result.data.0.tokenSymbol', 'LST');
    }

    public function test_it_filters_tokens_by_symbol(): void
    {
        Token::create(['address' => '0xaaaabbbbccccddddeeeeffff0000111122223333', 'name' => 'One', 'symbol' => 'ONE', 'total_supply' => 1]);
        Token::create(['address' => '0x9999888877776666555544443333222211110000', 'name' => 'Two', 'symbol' => 'TWO', 'total_supply' => 2]);

        $this->getJson(self::ENDPOINT.'?tokenSymbol[eq]=TWO')
            ->assertOk()
            ->assertJsonCount(1, 'result.data')
            ->assertJsonPath('result.data.0.tokenSymbol', 'TWO');
    }

    public function test_it_shows_a_token(): void
    {
        $token = Token::create([
            'address' => '0xaaaabbbbccccddddeeeeffff0000111122223333',
            'name' => 'Shown',
            'symbol' => 'SHW',
            'total_supply' => 7,
        ]);

        $this->getJson(self::ENDPOINT.'/'.$token->id)
            ->assertOk()
            ->assertJsonPath('result.data.tokenName', 'Shown');
    }

    public function test_it_returns_404_for_a_missing_token(): void
    {
        $this->getJson(self::ENDPOINT.'/999999')
            ->assertNotFound()
            ->assertJsonPath('error', 'Resource not found: Token not found with ID: 999999');
    }

    public function test_it_updates_a_token(): void
    {
        $token = Token::create([
            'address' => '0xaaaabbbbccccddddeeeeffff0000111122223333',
            'name' => 'Before',
            'symbol' => 'BFR',
            'total_supply' => 1,
        ]);

        $this->patchJson(self::ENDPOINT.'/'.$token->id, $this->payload([
            'tokenAddress' => '0x1111222233334444555566667777888899990000',
            'tokenName' => 'After',
            'tokenSymbol' => 'AFT',
        ]))
            ->assertOk()
            ->assertJsonPath('result.message', 'Token successfully updated')
            ->assertJsonPath('result.data.tokenName', 'After');

        $this->assertDatabaseHas('tokens', [
            'id' => $token->id,
            'name' => 'After',
            'symbol' => 'AFT',
        ]);
    }

    public function test_it_deletes_a_token(): void
    {
        $token = Token::create([
            'address' => '0xaaaabbbbccccddddeeeeffff0000111122223333',
            'name' => 'Doomed',
            'symbol' => 'DMD',
            'total_supply' => 1,
        ]);

        $this->deleteJson(self::ENDPOINT.'/'.$token->id)->assertNoContent();

        $this->assertDatabaseMissing('tokens', ['id' => $token->id]);
    }

    public function test_it_returns_404_when_deleting_a_missing_token(): void
    {
        $this->deleteJson(self::ENDPOINT.'/999999')
            ->assertNotFound()
            ->assertJsonPath('error', 'Resource not found: Token not found with ID: 999999');
    }
}
