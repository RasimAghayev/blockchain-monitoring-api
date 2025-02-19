<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('token_holders', function (Blueprint $table) {
            $table->id();
            $table->string('token_address');
            $table->string('holder_address');
            $table->decimal('balance', 40, 10);
            $table->decimal('percentage', 40, 10);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('token_holders');
    }
};
