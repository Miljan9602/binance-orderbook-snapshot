<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trade_aggregates', function (Blueprint $table) {
            $table->decimal('close_price', 20, 8)->nullable()->after('max_trade_size');
            $table->decimal('price_change_pct', 10, 6)->nullable()->after('close_price');
            $table->decimal('realized_vol_5m', 10, 6)->nullable()->after('price_change_pct');
        });
    }

    public function down(): void
    {
        Schema::table('trade_aggregates', function (Blueprint $table) {
            $table->dropColumn(['close_price', 'price_change_pct', 'realized_vol_5m']);
        });
    }
};
