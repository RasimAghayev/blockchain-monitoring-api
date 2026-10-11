<?php

declare(strict_types=1);

namespace Tests\Feature\Tokens;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TokenValidationTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/tokens';

    public static function invalidPayloads(): array
    {
        return [
            'missing address' => [['tokenAddress' => ''], 'tokenAddress'],
            'short address' => [['tokenAddress' => 'ab'], 'tokenAddress'],
            'missing name' => [['tokenName' => ''], 'tokenName'],
            'short name' => [['tokenName' => 'ab'], 'tokenName'],
            'missing symbol' => [['tokenSymbol' => ''], 'tokenSymbol'],
            'non numeric total supply' => [['tokenTotalSupply' => 'many'], 'tokenTotalSupply'],
            'zero total supply' => [['tokenTotalSupply' => 0], 'tokenTotalSupply'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_invalid_payloads(array $overrides, string $expectedField): void
    {
        $payload = array_merge([
            'tokenAddress' => '0x1234567890abcdef1234567890abcdef12345678',
            'tokenName' => 'MyToken',
            'tokenSymbol' => 'MTK',
            'tokenTotalSupply' => 1000,
        ], $overrides);

        $response = $this->postJson(self::ENDPOINT, $payload);

        $response->assertStatus(422);
        $this->assertArrayHasKey($expectedField, $response->json('error') ?? []);
    }

    public function test_it_requires_every_field(): void
    {
        $response = $this->postJson(self::ENDPOINT, []);

        $response->assertStatus(422);
        $errors = $response->json('error') ?? [];

        foreach (['tokenAddress', 'tokenName', 'tokenSymbol', 'tokenTotalSupply'] as $field) {
            $this->assertArrayHasKey($field, $errors);
        }
    }

    public function test_it_requires_a_numeric_route_id(): void
    {
        $this->getJson(self::ENDPOINT.'/not-a-number')->assertNotFound();
    }
}
