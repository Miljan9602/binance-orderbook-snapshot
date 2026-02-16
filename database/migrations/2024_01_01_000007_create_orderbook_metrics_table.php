<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orderbook_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained('trading_pairs')->cascadeOnDelete();
            $table->decimal('bid_volume', 20, 8);
            $table->decimal('ask_volume', 20, 8);
            $table->decimal('imbalance', 10, 6);
            $table->decimal('mid_price', 20, 8);
            $table->decimal('weighted_mid_price', 20, 8);
            $table->decimal('spread_bps', 10, 4);
            $table->timestamp('received_at');

            $table->index(['trading_pair_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orderbook_metrics');
    }
};
