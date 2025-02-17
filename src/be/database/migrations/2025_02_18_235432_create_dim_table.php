<?php

use App\Http\Controllers\Customers\Enums\CustomerTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dim_table', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('token_id')->nullable();
            $table->string('token_address')->nullable();
            $table->string('token_name')->nullable();
            $table->string('token_symbol')->nullable();
            $table->decimal('token_total_supply', 40, 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dim_table');
    }
};
