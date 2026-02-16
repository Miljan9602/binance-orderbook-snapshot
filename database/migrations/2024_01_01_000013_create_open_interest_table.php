<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_interest', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained('trading_pairs')->cascadeOnDelete();
            $table->decimal('open_interest', 20, 8);
            $table->timestamp('timestamp');
            $table->timestamp('received_at');

            $table->index(['trading_pair_id', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_interest');
    }
};
