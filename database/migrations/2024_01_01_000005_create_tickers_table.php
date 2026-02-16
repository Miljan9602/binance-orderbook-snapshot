<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->unique()->constrained('trading_pairs')->cascadeOnDelete();
            $table->decimal('price_change', 20, 8);
            $table->decimal('price_change_percent', 12, 4);
            $table->decimal('weighted_avg_price', 20, 8);
            $table->decimal('last_price', 20, 8);
            $table->decimal('last_quantity', 20, 8);
            $table->decimal('best_bid_price', 20, 8);
            $table->decimal('best_bid_quantity', 20, 8);
            $table->decimal('best_ask_price', 20, 8);
            $table->decimal('best_ask_quantity', 20, 8);
            $table->decimal('open_price', 20, 8);
            $table->decimal('high_price', 20, 8);
            $table->decimal('low_price', 20, 8);
            $table->decimal('volume', 20, 8);
            $table->decimal('quote_volume', 20, 8);
            $table->unsignedInteger('trade_count');
            $table->timestamp('received_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickers');
    }
};
