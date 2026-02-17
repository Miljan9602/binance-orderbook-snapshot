<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Binds all repository and service interfaces to their implementations.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array<string, string>
     */
    public array $bindings = [
        // Repository bindings
        \App\Contracts\Repositories\TradingPairRepositoryInterface::class => \App\Repositories\TradingPairRepository::class,
        \App\Contracts\Repositories\OrderbookRepositoryInterface::class => \App\Repositories\OrderbookRepository::class,
        \App\Contracts\Repositories\TradeRepositoryInterface::class => \App\Repositories\TradeRepository::class,
        \App\Contracts\Repositories\FuturesRepositoryInterface::class => \App\Repositories\FuturesRepository::class,
        \App\Contracts\Repositories\KlineRepositoryInterface::class => \App\Repositories\KlineRepository::class,
        \App\Contracts\Repositories\AnalyticsRepositoryInterface::class => \App\Repositories\AnalyticsRepository::class,

        // Web service bindings
        \App\Contracts\Services\DashboardServiceInterface::class => \App\Services\Web\DashboardService::class,
        \App\Contracts\Services\OrderbookQueryServiceInterface::class => \App\Services\Web\OrderbookQueryService::class,
        \App\Contracts\Services\AnalyticsServiceInterface::class => \App\Services\Web\AnalyticsService::class,
        \App\Contracts\Services\FuturesQueryServiceInterface::class => \App\Services\Web\FuturesQueryService::class,
        \App\Contracts\Services\KlineQueryServiceInterface::class => \App\Services\Web\KlineQueryService::class,
        \App\Contracts\Services\TechnicalIndicatorServiceInterface::class => \App\Services\Web\TechnicalIndicatorService::class,

        // Ingestion service bindings
        \App\Contracts\Services\OrderbookIngestionServiceInterface::class => \App\Services\Ingestion\OrderbookIngestionService::class,
        \App\Contracts\Services\TradeIngestionServiceInterface::class => \App\Services\Ingestion\TradeIngestionService::class,
        \App\Contracts\Services\TickerIngestionServiceInterface::class => \App\Services\Ingestion\TickerIngestionService::class,
        \App\Contracts\Services\KlineIngestionServiceInterface::class => \App\Services\Ingestion\KlineIngestionService::class,
        \App\Contracts\Services\TradeAggregationServiceInterface::class => \App\Services\Ingestion\TradeAggregationService::class,
        \App\Contracts\Services\LargeTradeDetectorInterface::class => \App\Services\Ingestion\LargeTradeDetector::class,
        \App\Contracts\Services\DataCleanupServiceInterface::class => \App\Services\Ingestion\DataCleanupService::class,
        \App\Contracts\Services\FuturesIngestionServiceInterface::class => \App\Services\Ingestion\FuturesIngestionService::class,
        \App\Contracts\Services\VpinComputationServiceInterface::class => \App\Services\Ingestion\VpinComputationService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
