<?php

declare(strict_types=1);

namespace Tests\Feature\Tokens;

use App\Http\Controllers\Tokens\Models\Token;
use App\Http\Controllers\Tokens\Models\TokenHolder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TokenBlockchainEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = '0xdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef';

    public function test_it_fetches_token_info_and_stores_it(): void
    {
        Http::fake([
            '*/token/*' => Http::response([
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'total_supply' => '115714953',
            ]),
        ]);

        $this->getJson('/api/v1/tokens/'.self::ADDRESS.'/info')
            ->assertOk()
            ->assertJsonPath('name', 'Ethereum')
            ->assertJsonPath('symbol', 'ETH')
            ->assertJsonPath('total_supply', '115714953');

        $this->assertDatabaseHas('tokens', [
            'address' => self::ADDRESS,
            'name' => 'Ethereum',
            'symbol' => 'ETH',
        ]);
    }

    public function test_it_reports_a_missing_token_from_the_upstream_api(): void
    {
        Http::fake(['*/token/*' => Http::response(['error' => 'not found'], 404)]);

        $this->getJson('/api/v1/tokens/'.self::ADDRESS.'/info')
            ->assertNotFound()
            ->assertJsonPath('error', 'Token info not found');
    }

    public function test_it_fetches_top_holders_and_stores_them(): void
    {
        Http::fake([
            '*/token/*/holders*' => Http::response([
                'holders' => [
                    ['address' => '0xaaaa', 'balance' => '100', 'percentage' => '50.5'],
                    ['address' => '0xbbbb', 'balance' => '50', 'percentage' => '25.25'],
                ],
            ]),
        ]);

        $this->getJson('/api/v1/tokens/'.self::ADDRESS.'/top-holders')
            ->assertOk()
            ->assertJsonPath('token_address', self::ADDRESS)
            ->assertJsonCount(2, 'top_holders');

        $this->assertSame(2, TokenHolder::query()->count());
        $this->assertDatabaseHas('token_holders', [
            'token_address' => self::ADDRESS,
            'holder_address' => '0xaaaa',
        ]);
    }

    public function test_it_replaces_stale_holders_on_a_refetch(): void
    {
        TokenHolder::create([
            'token_address' => self::ADDRESS,
            'holder_address' => '0xstale',
            'balance' => 1,
            'percentage' => 1,
        ]);

        Http::fake([
            '*/token/*/holders*' => Http::response([
                'holders' => [
                    ['address' => '0xaaaa', 'balance' => '100', 'percentage' => '50.5'],
                ],
            ]),
        ]);

        $this->getJson('/api/v1/tokens/'.self::ADDRESS.'/top-holders')->assertOk();

        $this->assertSame(1, TokenHolder::query()->count());
        $this->assertDatabaseMissing('token_holders', ['holder_address' => '0xstale']);
    }

    public function test_it_updates_an_existing_token_from_the_upstream_api(): void
    {
        Token::create([
            'address' => self::ADDRESS,
            'name' => 'Stale',
            'symbol' => 'OLD',
            'total_supply' => 1,
        ]);

        Http::fake([
            '*/token/*' => Http::response([
                'name' => 'Fresh',
                'symbol' => 'NEW',
                'total_supply' => '2',
            ]),
        ]);

        $this->getJson('/api/v1/tokens/'.self::ADDRESS.'/info')->assertOk();

        $this->assertSame(1, Token::query()->count());
        $this->assertDatabaseHas('tokens', [
            'address' => self::ADDRESS,
            'name' => 'Fresh',
            'symbol' => 'NEW',
        ]);
    }
}
