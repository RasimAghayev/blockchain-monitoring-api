<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * dim_table and token_holders were created with zero FKs/indexes
     * (docs/REPOSITORY_AUDIT.md P2-1/P2-2). This backfills the constraints that
     * match how the application actually reads/writes these tables today:
     *
     * - dim_table.token_id: TokenObserver (app/Http/Controllers/Tokens/Observers/
     *   TokenObserver.php) maintains this as a strict 1:1 mirror of a token via
     *   `DIM::updateOrCreate(['token_id' => $token->id], ...)`. Without a unique
     *   constraint, two concurrent writes for the same token (e.g. two queue
     *   workers processing overlapping `Token::updated` events under horizontal
     *   scaling) can both pass updateOrCreate's own SELECT check and insert a
     *   duplicate row — the classic read-then-write race a DB-level constraint
     *   closes, the app-level check alone cannot. A real FK to tokens.id with
     *   onDelete('cascade') is added too: TokenObserver::deleted() already
     *   deletes the DIM row explicitly, but mass-delete queries (e.g.
     *   Token::where(...)->delete()) don't fire per-row model events in Eloquent,
     *   so the FK is the only thing that guarantees no orphaned dim_table row
     *   survives a token's deletion by any path.
     * - token_holders.token_address: TokenController::refreshTopHolders() (or its
     *   equivalent write path) does `TokenHolder::where('token_address', ...)
     *   ->delete()` followed by a bulk insert on every top-holders refresh, with
     *   no index backing that WHERE clause today — a full table scan on every
     *   refresh as the table grows across all monitored tokens.
     */
    public function up(): void
    {
        Schema::table('dim_table', function (Blueprint $table) {
            $table->unique('token_id');
            $table->foreign('token_id')->references('id')->on('tokens')->onDelete('cascade');
        });

        Schema::table('token_holders', function (Blueprint $table) {
            $table->index('token_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dim_table', function (Blueprint $table) {
            $table->dropForeign(['token_id']);
            $table->dropUnique(['token_id']);
        });

        Schema::table('token_holders', function (Blueprint $table) {
            $table->dropIndex(['token_address']);
        });
    }
};
