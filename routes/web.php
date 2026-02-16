<?php

use App\Http\Controllers\Admin\TradingPairController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.trading-pairs.index');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.trading-pairs.index'));
    Route::get('/trading-pairs', [TradingPairController::class, 'index'])->name('trading-pairs.index');
    Route::get('/trading-pairs/data', [TradingPairController::class, 'indexData'])->name('trading-pairs.index-data');
    Route::get('/trading-pairs/{tradingPair}', [TradingPairController::class, 'show'])->name('trading-pairs.show');
    Route::get('/trading-pairs/{tradingPair}/data', [TradingPairController::class, 'showData'])->name('trading-pairs.show-data');
    Route::post('/trading-pairs/{tradingPair}/toggle', [TradingPairController::class, 'toggle'])->name('trading-pairs.toggle');
});
