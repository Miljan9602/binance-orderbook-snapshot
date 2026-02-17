<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orderbook_walls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained()->cascadeOnDelete();
            $table->string('side');
            $table->decimal('price', 20, 8);
            $table->decimal('quantity', 20, 8);
            $table->decimal('avg_level_quantity', 20, 8);
            $table->float('size_multiple');
            $table->string('status')->default('ACTIVE');
            $table->timestamp('detected_at');
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->index(['trading_pair_id', 'status', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orderbook_walls');
    }
};
