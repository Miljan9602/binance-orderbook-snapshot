<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique();
            $table->string('base_asset');
            $table->string('quote_asset');
            $table->string('stream_name');
            $table->boolean('is_active')->default(true);
            $table->integer('depth_level')->default(20);
            $table->timestamp('last_update_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_pairs');
    }
};
