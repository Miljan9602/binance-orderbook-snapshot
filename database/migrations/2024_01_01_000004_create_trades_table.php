<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained('trading_pairs')->cascadeOnDelete();
            $table->bigInteger('agg_trade_id');
            $table->decimal('price', 20, 8);
            $table->decimal('quantity', 20, 8);
            $table->bigInteger('first_trade_id');
            $table->bigInteger('last_trade_id');
            $table->boolean('is_buyer_maker');
            $table->timestamp('traded_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['trading_pair_id', 'traded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
