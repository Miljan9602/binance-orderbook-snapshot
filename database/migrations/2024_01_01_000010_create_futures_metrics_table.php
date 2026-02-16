<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('futures_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->unique()->constrained('trading_pairs')->cascadeOnDelete();
            $table->decimal('mark_price', 20, 8);
            $table->decimal('index_price', 20, 8);
            $table->decimal('funding_rate', 20, 10);
            $table->timestamp('next_funding_time')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('futures_metrics');
    }
};
