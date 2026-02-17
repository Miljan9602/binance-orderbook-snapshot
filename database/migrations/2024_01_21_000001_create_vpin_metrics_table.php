<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpin_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained()->cascadeOnDelete();
            $table->decimal('vpin', 10, 6);
            $table->decimal('bucket_volume', 20, 8);
            $table->integer('bucket_count');
            $table->integer('window_size');
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->index(['trading_pair_id', 'computed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpin_metrics');
    }
};
