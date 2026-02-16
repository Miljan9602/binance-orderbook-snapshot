<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orderbook_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->unique()->constrained('trading_pairs')->cascadeOnDelete();
            $table->bigInteger('last_update_id');
            $table->jsonb('bids');
            $table->jsonb('asks');
            $table->decimal('best_bid_price', 20, 8);
            $table->decimal('best_ask_price', 20, 8);
            $table->decimal('spread', 20, 8);
            $table->timestamp('received_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orderbook_snapshots');
    }
};
