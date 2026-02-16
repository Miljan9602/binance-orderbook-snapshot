<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained('trading_pairs')->cascadeOnDelete();
            $table->string('interval', 10);
            $table->timestamp('period_start');
            $table->decimal('vwap', 20, 8);
            $table->decimal('buy_volume', 20, 8);
            $table->decimal('sell_volume', 20, 8);
            $table->decimal('cvd', 20, 8);
            $table->unsignedInteger('trade_count');
            $table->decimal('avg_trade_size', 20, 8);
            $table->decimal('max_trade_size', 20, 8);

            $table->unique(['trading_pair_id', 'interval', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_aggregates');
    }
};
