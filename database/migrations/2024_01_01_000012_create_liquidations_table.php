<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained('trading_pairs')->cascadeOnDelete();
            $table->string('side', 10);
            $table->string('order_type', 20);
            $table->decimal('price', 20, 8);
            $table->decimal('quantity', 20, 8);
            $table->decimal('avg_price', 20, 8);
            $table->string('order_status', 20);
            $table->timestamp('order_time');
            $table->timestamp('received_at');

            $table->index(['trading_pair_id', 'order_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidations');
    }
};
