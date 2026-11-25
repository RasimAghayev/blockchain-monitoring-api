<?php

declare(strict_types=1);

namespace Tests\Feature\Checks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the liveness/readiness split added in
 * docs/adr/0003-liveness-readiness-split.md. Both routes are unauthenticated
 * by design — a container orchestrator probing them has no credentials.
 */
class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_liveness_is_always_ok_without_authentication(): void
    {
        $this->getJson('/api/v1/health/live')
            ->assertOk()
            ->assertJsonPath('result.status', 'alive');
    }

    public function test_readiness_is_ok_when_the_database_is_reachable(): void
    {
        $this->getJson('/api/v1/health/ready')
            ->assertOk()
            ->assertJsonPath('result.status', 'ready');
    }

    public function test_readiness_returns_503_when_the_database_is_unreachable(): void
    {
        // Point the default connection at a sqlite file whose directory does
        // not exist, so DB::connection()->getPdo() actually throws, without
        // needing to stop a real database service mid-test-run. Defined as a
        // brand-new 'broken' connection (rather than mutating the real
        // 'sqlite' one in place) so RefreshDatabase's own bookkeeping (it
        // reads config('database.default') again when rolling back in
        // tearDown) still finds the original connection — the default is
        // restored before this method returns, specifically so that
        // teardown's rollback runs against the same 'sqlite' connection
        // setUp() began its transaction on, not against 'broken'. Leaving the
        // mutation in place past this test previously cascaded into "cannot
        // start a transaction within a transaction" on every test that ran
        // after it in the same process.
        $originalDefault = config('database.default');

        config(['database.connections.broken' => [
            'driver' => 'sqlite',
            'database' => '/nonexistent-directory/unreachable.sqlite',
        ]]);
        config(['database.default' => 'broken']);

        try {
            $this->getJson('/api/v1/health/ready')->assertStatus(503);
        } finally {
            config(['database.default' => $originalDefault]);
        }
    }
}
