<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('large_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('trade_id')->nullable();
            $table->decimal('price', 20, 8);
            $table->decimal('quantity', 20, 8);
            $table->boolean('is_buyer_maker');
            $table->decimal('avg_trade_size', 20, 8);
            $table->decimal('size_multiple', 10, 2);
            $table->timestamp('traded_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['trading_pair_id', 'traded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('large_trades');
    }
};
