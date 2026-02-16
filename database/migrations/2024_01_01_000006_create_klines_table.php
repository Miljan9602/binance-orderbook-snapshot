<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained('trading_pairs')->cascadeOnDelete();
            $table->string('interval', 10);
            $table->timestamp('open_time');
            $table->timestamp('close_time');
            $table->decimal('open', 20, 8);
            $table->decimal('high', 20, 8);
            $table->decimal('low', 20, 8);
            $table->decimal('close', 20, 8);
            $table->decimal('volume', 20, 8);
            $table->decimal('quote_volume', 20, 8);
            $table->decimal('taker_buy_volume', 20, 8);
            $table->decimal('taker_buy_quote_volume', 20, 8);
            $table->unsignedInteger('trade_count');
            $table->boolean('is_closed')->default(false);
            $table->timestamp('received_at');
            $table->timestamps();

            $table->unique(['trading_pair_id', 'interval', 'open_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klines');
    }
};
