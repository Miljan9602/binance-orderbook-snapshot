<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orderbook_metrics', function (Blueprint $table) {
            $table->index(['trading_pair_id', 'id']);
        });

        Schema::table('futures_metrics_history', function (Blueprint $table) {
            $table->index(['trading_pair_id', 'id']);
        });

        Schema::table('open_interest', function (Blueprint $table) {
            $table->index(['trading_pair_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('orderbook_metrics', function (Blueprint $table) {
            $table->dropIndex(['trading_pair_id', 'id']);
        });

        Schema::table('futures_metrics_history', function (Blueprint $table) {
            $table->dropIndex(['trading_pair_id', 'id']);
        });

        Schema::table('open_interest', function (Blueprint $table) {
            $table->dropIndex(['trading_pair_id', 'id']);
        });
    }
};
