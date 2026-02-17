<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FuturesController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\KlineController;
use App\Http\Controllers\Admin\OrderbookController;
use App\Http\Controllers\Admin\TradingPairSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.trading-pairs.index');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.trading-pairs.index'));
    Route::get('/trading-pairs', [DashboardController::class, 'index'])->name('trading-pairs.index');
    Route::get('/trading-pairs/data', [DashboardController::class, 'data'])->name('trading-pairs.index-data');
    Route::get('/trading-pairs/{tradingPair}', [OrderbookController::class, 'show'])->name('trading-pairs.show');
    Route::get('/trading-pairs/{tradingPair}/history', [HistoryController::class, 'index'])->name('trading-pairs.history');
    Route::get('/trading-pairs/{tradingPair}/data', [OrderbookController::class, 'data'])->name('trading-pairs.show-data');
    Route::get('/trading-pairs/{tradingPair}/analytics', [AnalyticsController::class, 'index'])->name('trading-pairs.analytics');
    Route::get('/trading-pairs/{tradingPair}/analytics/data', [AnalyticsController::class, 'data'])->name('trading-pairs.analytics-data');
    Route::get('/trading-pairs/{tradingPair}/analytics/depth-data', [AnalyticsController::class, 'depthData'])->name('trading-pairs.analytics-depth-data');
    Route::get('/trading-pairs/{tradingPair}/analytics/distributions', [AnalyticsController::class, 'distributions'])->name('trading-pairs.analytics-distributions');
    Route::get('/trading-pairs/{tradingPair}/analytics/regime', [AnalyticsController::class, 'regime'])->name('trading-pairs.analytics-regime');
    Route::get('/trading-pairs/{tradingPair}/analytics/correlations', [AnalyticsController::class, 'correlations'])->name('trading-pairs.analytics-correlations');
    Route::get('/trading-pairs/{tradingPair}/futures', [FuturesController::class, 'index'])->name('trading-pairs.futures');
    Route::get('/trading-pairs/{tradingPair}/futures/data', [FuturesController::class, 'data'])->name('trading-pairs.futures-data');
    Route::get('/trading-pairs/{tradingPair}/klines', [KlineController::class, 'index'])->name('trading-pairs.klines');
    Route::get('/trading-pairs/{tradingPair}/klines/data', [KlineController::class, 'data'])->name('trading-pairs.klines-data');
    Route::post('/trading-pairs/{tradingPair}/toggle', [TradingPairSettingsController::class, 'toggle'])->name('trading-pairs.toggle');
});
