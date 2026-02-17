# Binance Orderbook Monitor

## Project Overview
Laravel 12 app that streams real-time orderbook data from Binance WebSocket API and displays it in a professional exchange-style UI. Built for NIMA Research quantitative analysis.

## Tech Stack
- PHP 8.2+ / Laravel 12
- SQLite database (default) — can be swapped to MySQL/Postgres
- Tailwind CSS via CDN (no build step for styles)
- Chart.js 4.4.7 for analytics/dashboard/futures charts
- TradingView Lightweight Charts 4.2.1 for kline candlestick charts
- Ratchet/Pawl for WebSocket client
- ReactPHP event loop for long-running WebSocket process
- PHPUnit for testing (152 tests, 521 assertions)

## Architecture

### Design Principles
- **SOLID**: Single Responsibility controllers/services, Dependency Inversion via interfaces, Interface Segregation with narrow contracts
- **Repository Pattern**: All database queries go through repository interfaces
- **Service Layer**: Web query services orchestrate repositories, ingestion services handle data persistence
- **DTOs**: Readonly typed data transfer objects for filters and response data
- **Form Request Validation**: All user input validated through dedicated FormRequest classes

### Data Flow
- Spot: Binance WS -> BinanceWebSocketService -> Ingestion Services -> Database -> Blade views (auto-refresh 300ms)
- Futures: Binance Futures WS -> BinanceFuturesWebSocketService -> FuturesIngestionService -> Database -> Blade views (auto-refresh 1s)
- Computed: OrderbookIngestionService computes metrics inline (sampled) + scheduled trade aggregation + wall detection
- VPIN: Scheduled every minute via `vpin:compute` — volume-synchronized bucketing from raw trades
- Web: Controller -> FormRequest -> Web Service -> Repository -> DTO -> View/JSON

### WebSocket Streams — Spot (combined connection)
- `seiusdc@depth20` — Top 20 bid/ask levels (orderbook)
- `seiusdc@aggTrade` — Aggregated trades (price, qty, buy/sell)
- `seiusdc@kline_1m` — 1-minute OHLCV candles
- `seiusdc@kline_5m` — 5-minute OHLCV candles
- `seiusdc@kline_15m` — 15-minute OHLCV candles
- `seiusdc@kline_1h` — 1-hour OHLCV candles
- `seiusdc@ticker` — 24hr rolling stats

### WebSocket Streams — Futures (separate connection to fstream.binance.com)
- `seiusdt@markPrice@1s` — Mark price, index price, funding rate
- `seiusdt@forceOrder` — Liquidation orders

### Database Tables (15 tables)
- `trading_pairs` — Symbol config (SEIUSDC), active flag, depth level, futures_symbol
- `orderbook_snapshots` — Latest orderbook state (1 row per pair, upserted)
- `orderbook_history` — Historical orderbook snapshots (append-only, cleaned hourly)
- `trades` — Aggregated trades (append-only, cleaned hourly)
- `tickers` — 24hr ticker stats (1 row per pair, upserted)
- `klines` — Candlestick data for all intervals (upserted while open, old closed cleaned hourly)
- `orderbook_metrics` — Computed OB analytics: imbalance, VWAP mid, spread bps (append-only, sampled)
- `trade_aggregates` — 1m rollups: VWAP, CVD, buy/sell volume, trade stats, realized volatility (5-period)
- `large_trades` — Detected large trades exceeding size threshold (append-only)
- `futures_metrics` — Latest mark price, funding rate (1 row per pair, upserted)
- `futures_metrics_history` — Historical futures metrics (append-only, sampled)
- `liquidations` — Force liquidation orders (append-only)
- `open_interest` — Polled OI snapshots (append-only)
- `orderbook_walls` — Detected orderbook walls: side, price, quantity, size_multiple, status (ACTIVE/REMOVED), detected_at, removed_at
- `vpin_metrics` — VPIN (order flow toxicity): vpin value, bucket_volume, bucket_count, window_size, computed_at

### Models (15 models, all with HasFactory + scopes)
- `TradingPair` — has: snapshot, history, trades, ticker, klines, orderbookMetrics, tradeAggregates, largeTrades, futuresMetric, futuresMetricsHistory, liquidations, openInterest, walls, vpinMetrics. Scopes: `active()`, `hasFuturesSymbol()`
- `OrderbookSnapshot` — current orderbook state per pair. Scope: `forTradingPair()`
- `OrderbookHistory` — historical orderbook records. Scope: `forTradingPair()`
- `Trade` — aggregated trade records (timestamps managed manually). Scope: `forTradingPair()`
- `Ticker` — 24hr rolling ticker stats. Scope: `forTradingPair()`
- `Kline` — candlestick/OHLCV data (all intervals). Scopes: `forTradingPair()`, `open()`
- `OrderbookMetric` — computed orderbook analytics (sampled). Scope: `forTradingPair()`
- `TradeAggregate` — periodic trade rollups (includes realized volatility columns). Scope: `forTradingPair()`
- `LargeTrade` — detected large trades exceeding configurable size threshold. Scope: `forTradingPair()`
- `FuturesMetric` — latest futures data per pair. Scope: `forTradingPair()`
- `FuturesMetricHistory` — historical futures metrics. Scope: `forTradingPair()`
- `Liquidation` — forced liquidation orders. Scope: `forTradingPair()`
- `OpenInterest` — polled open interest data. Scope: `forTradingPair()`
- `OrderbookWall` — detected orderbook walls with status tracking. Scopes: `forTradingPair()`, `active()`
- `VpinMetric` — VPIN order flow toxicity metric. Scope: `forTradingPair()`

### Contracts (Interfaces)

**Repository Contracts** (`app/Contracts/Repositories/`):
- `TradingPairRepositoryInterface` — pair CRUD, active pairs, eager loading
- `OrderbookRepositoryInterface` — snapshots, history, metrics, sparkline data, recent walls, active walls
- `TradeRepositoryInterface` — trades, aggregates, large trades, hourly stats, cumulative CVD before timestamp
- `FuturesRepositoryInterface` — futures history, open interest, liquidations
- `KlineRepositoryInterface` — filtered klines, chart klines
- `AnalyticsRepositoryInterface` — filtered metrics, hourly orderbook stats

**Service Contracts** (`app/Contracts/Services/`):
- `DashboardServiceInterface` — dashboard pairs + summary data
- `OrderbookQueryServiceInterface` — orderbook view data assembly
- `AnalyticsServiceInterface` — chart data, depth heatmap, distributions, market regime, correlations
- `FuturesQueryServiceInterface` — futures chart data assembly
- `KlineQueryServiceInterface` — kline chart data with optional technical indicators
- `TechnicalIndicatorServiceInterface` — RSI, Bollinger Bands, EMA, MACD, taker ratio computation
- `OrderbookIngestionServiceInterface` — orderbook data persistence + metrics + wall detection
- `TradeIngestionServiceInterface` — trade persistence + large trade detection
- `TickerIngestionServiceInterface` — ticker data persistence
- `KlineIngestionServiceInterface` — kline data persistence
- `TradeAggregationServiceInterface` — trade aggregate computation
- `LargeTradeDetectorInterface` — large trade threshold detection
- `DataCleanupServiceInterface` — data retention cleanup
- `FuturesIngestionServiceInterface` — futures data persistence + OI polling
- `VpinComputationServiceInterface` — VPIN computation for all active pairs

### DTOs

**Filter DTOs** (`app/DTOs/Filters/`):
- `DateRangeFilter` — base filter with `from`/`to` dates
- `HistoryFilter` — extends DateRangeFilter with `minSpread`/`maxSpread`
- `AnalyticsFilter` — metrics and aggregate date ranges
- `FuturesFilter` — history and OI date ranges
- `KlineFilter` — interval + date range

**Response DTOs** (`app/DTOs/`):
- `Dashboard/DashboardSummaryData` — dashboard summary with sparkline + futures data + quoteVolume, weightedAvgPrice, openPrice
- `Orderbook/OrderbookViewData` — snapshot + ticker + trades for orderbook page
- `Analytics/AnalyticsChartData` — metrics, aggregates, trades, volatility, large trades, cumulative CVD, buy/sell ratio, orderbook walls, VPIN
- `Analytics/DepthHeatmapData` — heatmap matrix with timestamps, price levels, bid/ask heat
- `Analytics/DistributionData` — spread histogram + hourly stats
- `Analytics/MarketRegimeData` — regime classification (TRENDING_UP/DOWN, RANGING, VOLATILE), confidence, signals
- `Analytics/CorrelationData` — 4 scatter datasets (OI vs Price, Funding vs Premium, Volume vs Volatility, Imbalance vs Price)
- `Futures/FuturesChartData` — funding, OI, premium, liquidation charts + annualized funding, mark/index prices, OI delta, notional liquidations

### Controllers (7 focused controllers in `app/Http/Controllers/Admin/`)
- `DashboardController` — `index()`, `data()` — dashboard page + JSON API
- `OrderbookController` — `show()`, `data()` — orderbook detail + JSON API
- `HistoryController` — `index()` — orderbook history (paginated, filterable)
- `AnalyticsController` — `index()`, `data()`, `depthData()`, `distributions()`, `regime()`, `correlations()` — analytics page + 5 JSON APIs
- `FuturesController` — `index()`, `data()` — futures page + JSON API
- `KlineController` — `index()`, `data()` — klines page + JSON API
- `TradingPairSettingsController` — `toggle()` — activate/deactivate streaming

### Form Requests (`app/Http/Requests/Admin/`)
- `HistoryFilterRequest` — validates from, to, min_spread, max_spread
- `AnalyticsFilterRequest` — validates metrics_from/to, agg_from/to
- `FuturesFilterRequest` — validates history_from/to, oi_from/to
- `KlineFilterRequest` — validates interval (1m/5m/15m/1h), from, to
- `KlineDataRequest` — validates interval + optional indicators for JSON API

### Repositories (`app/Repositories/`)
- `TradingPairRepository` — TradingPair queries with eager loading
- `OrderbookRepository` — snapshot, history, metrics, sparkline, wall queries
- `TradeRepository` — trade, aggregate, large trade, hourly stat, cumulative CVD queries
- `FuturesRepository` — futures history, OI, liquidation queries
- `KlineRepository` — kline queries with interval/date filtering
- `AnalyticsRepository` — metric queries, hourly orderbook stats

### Web Services (`app/Services/Web/`)
- `DashboardService` — assembles dashboard pairs + summary data with sparkline
- `OrderbookQueryService` — assembles orderbook view with snapshot, ticker, trades
- `AnalyticsService` — chart data (incl. cumulative CVD, buy/sell ratio, walls, VPIN), depth heatmap, spread histogram, hourly stats, market regime classification, cross-metric correlations
- `FuturesQueryService` — premium calculation, liquidation grouping, chart assembly (incl. annualized funding, mark/index prices, OI delta, notional)
- `KlineQueryService` — kline chart data formatting with optional technical indicator computation
- `TechnicalIndicatorService` — computes RSI (14-period Wilder's), Bollinger Bands (20-period, 2 stddev), EMA 20/50, MACD (12, 26, 9), taker buy/sell ratio

### Ingestion Services (`app/Services/Ingestion/`)
- `OrderbookIngestionService` — snapshot upsert, history append, metrics delegation, wall detection
- `TradeIngestionService` — trade creation + large trade detection delegation
- `TickerIngestionService` — ticker upsert
- `KlineIngestionService` — kline upsert
- `TradeAggregationService` — VWAP, CVD, volatility computation for 1m rollups
- `LargeTradeDetector` — rolling window, threshold-based large trade detection
- `DataCleanupService` — retention-based cleanup for all spot + futures tables + walls + VPIN
- `FuturesIngestionService` — mark price, liquidation, OI persistence + history sampling
- `VpinComputationService` — volume-synchronized bucketing VPIN algorithm from raw trades

### Console Commands (`app/Console/Commands/`)
- `BinanceWebSocket` — `binance:websocket` — Start spot WebSocket client (long-running)
- `BinanceFuturesWebSocket` — `binance:futures-websocket` — Start futures WebSocket client (long-running)
- `CleanOrderbookHistory` — `orderbook:clean-history` — Clean old spot data
- `CleanFuturesHistory` — `binance:clean-futures-history` — Clean old futures data
- `AggregateTradesCommand` — `trades:aggregate` — Aggregate trades from last minute
- `FetchOpenInterest` — `binance:fetch-open-interest` — Poll open interest from REST API
- `ComputeVpinCommand` — `vpin:compute` — Compute VPIN for all active trading pairs

### WebSocket Services
- `BinanceWebSocketService` — Spot WebSocket connection, routes streams to ingestion services
- `BinanceFuturesWebSocketService` — Futures WebSocket connection to fstream.binance.com

### Service Provider
- `RepositoryServiceProvider` — binds all 6 repository interfaces + 15 service interfaces to implementations

## Key Files
- `app/Contracts/` — All repository and service interfaces
- `app/DTOs/` — All filter and response DTOs
- `app/Repositories/` — All repository implementations
- `app/Services/Web/` — Web query services (used by controllers)
- `app/Services/Ingestion/` — Data ingestion services (used by WebSocket + scheduler)
- `app/Services/BinanceWebSocketService.php` — Spot WebSocket client, stream routing
- `app/Services/BinanceFuturesWebSocketService.php` — Futures WebSocket client
- `app/Http/Controllers/Admin/` — 7 focused controllers
- `app/Http/Requests/Admin/` — 5 form request validators
- `app/Console/Commands/` — 7 artisan commands
- `app/Providers/RepositoryServiceProvider.php` — Interface-to-implementation bindings
- `config/binance.php` — WS URLs, depth level, reconnect delays, retention, metrics interval, futures config, wall detection, regime thresholds, VPIN config
- `database/seeders/TradingPairSeeder.php` — Seeds SEIUSDC pair with futures_symbol
- `routes/console.php` — Scheduling: cleanup hourly, trade aggregation every minute, OI every 30s, VPIN every minute
- `routes/web.php` — All 20 routes mapped to controllers

## Commands
```bash
php artisan binance:websocket           # Start spot WebSocket client (long-running)
php artisan binance:futures-websocket   # Start futures WebSocket client (long-running)
php artisan orderbook:clean-history     # Clean old spot history/trades/klines/metrics/walls/vpin
php artisan binance:clean-futures-history  # Clean old futures history/liquidations/OI
php artisan trades:aggregate            # Aggregate trades from last minute
php artisan binance:fetch-open-interest # Poll open interest from REST API
php artisan vpin:compute                # Compute VPIN for all active pairs
php artisan db:seed --class=TradingPairSeeder  # Seed trading pair
php artisan migrate                     # Run migrations
php artisan serve                       # Start web server
php artisan schedule:work               # Run scheduler
php artisan test                        # Run 152 tests (feature + unit)
```

## Routes (20 routes)
- `GET /` — Redirects to dashboard
- `GET /admin` — Redirects to dashboard
- `GET /admin/trading-pairs` — Dashboard with price, stats, cards → `DashboardController@index`
- `GET /admin/trading-pairs/data` — JSON API for dashboard → `DashboardController@data`
- `GET /admin/trading-pairs/{id}` — Professional orderbook (3-column) + depth chart → `OrderbookController@show`
- `GET /admin/trading-pairs/{id}/data` — JSON API for orderbook page → `OrderbookController@data`
- `GET /admin/trading-pairs/{id}/history` — Orderbook history (paginated, filterable) → `HistoryController@index`
- `GET /admin/trading-pairs/{id}/analytics` — Analytics page (metrics + trade aggregates) → `AnalyticsController@index`
- `GET /admin/trading-pairs/{id}/analytics/data` — JSON API for live analytics → `AnalyticsController@data`
- `GET /admin/trading-pairs/{id}/analytics/depth-data` — JSON API for depth heatmap → `AnalyticsController@depthData`
- `GET /admin/trading-pairs/{id}/analytics/distributions` — JSON API for distributions → `AnalyticsController@distributions`
- `GET /admin/trading-pairs/{id}/analytics/regime` — JSON API for market regime classification → `AnalyticsController@regime`
- `GET /admin/trading-pairs/{id}/analytics/correlations` — JSON API for cross-metric correlations → `AnalyticsController@correlations`
- `GET /admin/trading-pairs/{id}/futures` — Futures page → `FuturesController@index`
- `GET /admin/trading-pairs/{id}/futures/data` — JSON API for futures → `FuturesController@data`
- `GET /admin/trading-pairs/{id}/klines` — Multi-timeframe klines (1m/5m/15m/1h) → `KlineController@index`
- `GET /admin/trading-pairs/{id}/klines/data` — JSON API for candlestick + indicators data → `KlineController@data`
- `POST /admin/trading-pairs/{id}/toggle` — Activate/deactivate streaming → `TradingPairSettingsController@toggle`

## Testing

### Test Infrastructure
- `tests/TestCase.php` — Base test case with `RefreshDatabase`
- `tests/Traits/CreatesTestData.php` — Shared test data helpers
- 15 model factories in `database/factories/` (+ UserFactory)

### Feature Tests (`tests/Feature/Admin/` — 15 files)
- `RedirectTest` — Root and admin redirects
- `DashboardIndexTest` — Dashboard page loads, empty state
- `DashboardDataTest` — JSON API: full data, no snapshot, no ticker, sparkline, zero bid
- `OrderbookShowTest` — Orderbook detail page
- `OrderbookDataTest` — Orderbook JSON API
- `HistoryTest` — History: filters (from, min/max spread, combined), pagination, ordering, empty
- `AnalyticsIndexTest` — Analytics page with data + empty state
- `AnalyticsDataTest` — Analytics JSON: full, empty, side mapping, null volatility, ordering, cumulative CVD (structure + accumulation), buy/sell ratio, orderbook walls, VPIN (structure + null), regime (structure + default), correlations (structure + empty + volume/volatility + funding/premium)
- `DepthDataTest` — Depth heatmap JSON structure
- `DistributionsTest` — Distribution JSON: spread histogram, hourly stats
- `FuturesIndexTest` — Futures page loads
- `FuturesDataTest` — Futures JSON: full, premium calc, liquidation grouping
- `KlinesIndexTest` — Klines page: default/valid/invalid interval, filters, empty, ordering
- `KlinesDataTest` — Klines JSON: OHLCV structure, limit 200, interval filter, validation, indicators
- `ToggleTest` — Toggle activate/deactivate + redirect

### Unit Tests (`tests/Unit/Services/` — 4 files)
- `IngestionServiceTest` — 22 tests: orderbook upsert, trade save, large trade detection, ticker, kline, metrics computation, trade aggregation, data cleanup
- `FuturesIngestionServiceTest` — 10 tests: mark price, liquidation, OI fetch, cleanup
- `TechnicalIndicatorServiceTest` — 13 tests: RSI, Bollinger Bands, EMA, MACD, taker ratio, edge cases
- `VpinComputationServiceTest` — 12 tests: VPIN computation, edge cases, bucket volume, cleanup

## Environment Variables (config/binance.php)
- `BINANCE_WS_BASE_URL` — default: `wss://stream.binance.com:9443`
- `BINANCE_DEPTH_LEVEL` — default: `20`
- `BINANCE_RECONNECT_BASE_DELAY` — default: `5` (seconds)
- `BINANCE_RECONNECT_MAX_DELAY` — default: `60` (seconds)
- `BINANCE_HISTORY_RETENTION_HOURS` — default: `24`
- `BINANCE_METRICS_SAMPLE_INTERVAL` — default: `22` (seconds between orderbook metric/futures history writes)
- `BINANCE_FUTURES_WS_BASE_URL` — default: `wss://fstream.binance.com`
- `BINANCE_FUTURES_REST_BASE_URL` — default: `https://fapi.binance.com`
- `BINANCE_FUTURES_OI_INTERVAL` — default: `30` (seconds between OI polls)
- `BINANCE_LARGE_TRADE_MULTIPLIER` — default: `3` (trade size / avg size threshold for large trade detection)
- `BINANCE_LARGE_TRADE_WINDOW` — default: `100` (number of recent trades to compute average size)
- `BINANCE_WALL_DETECTION_MULTIPLIER` — default: `3` (orderbook level qty / avg level qty threshold)
- `BINANCE_REGIME_CVD_THRESHOLD` — default: `500` (CVD magnitude for trending classification)
- `BINANCE_REGIME_VOLATILITY_THRESHOLD` — default: `0.02` (realized vol threshold for volatile regime)
- `BINANCE_REGIME_SPREAD_THRESHOLD` — default: `10` (spread bps threshold for volatile regime)
- `BINANCE_REGIME_LARGE_TRADE_THRESHOLD` — default: `3` (large trade count for trending signal)
- `BINANCE_REGIME_LOOKBACK` — default: `10` (number of recent aggregates for regime analysis)
- `BINANCE_VPIN_BUCKET_COUNT` — default: `20` (number of volume buckets for VPIN)
- `BINANCE_VPIN_WINDOW` — default: `10` (rolling window of buckets for VPIN calculation)

## Procfile
```
web: php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
websocket: php artisan binance:websocket          # spot streams (7 streams)
futures: php artisan binance:futures-websocket     # futures streams (mark price, liquidations)
scheduler: php artisan schedule:work               # trade aggregation + OI polling + VPIN + cleanup
```

## UI Notes
- Dark "Deep Space" theme (gray-950 bg), monospace tabular-nums for all prices, glass-card styling with backdrop-blur
- **Spot pages**: Cyan accent (`cyan-500` = `#06b6d4`) — stat card borders, buttons, charts, badges
- **Futures page**: Violet accent (`violet-500` = `#8b5cf6`) — card borders, charts, header text
- **Bid/Ask colors**: Emerald (buy/bid) / Rose (sell/ask)
- **Logo/Pagination**: Neutral gray gradients

### Dashboard
- Hero price with mini sparkline (Chart.js), 24h change %, stat cards (24h Quote Vol, VWAP, 24h Open)
- Ticker stats grid, nav buttons to all pages

### Orderbook (Live)
- Asks reversed (lowest near spread), depth bars (CSS width %), cumulative totals
- Spread/mid-price row, imbalance bar
- Chart.js depth area chart: bid (emerald gradient) / ask (rose gradient) cumulative curves, updates every 300ms

### Analytics (Live, 1s refresh)
- 7 live metric cards: Bid Volume, Ask Volume, Imbalance, Mid Price, Weighted Mid, Spread (bps), VPIN Toxicity
- Market Regime badge (green=trending up, red=trending down, amber=ranging, violet=volatile), refreshes 10s
- 16 Chart.js charts:
  - Mid Price (full width), Imbalance, Spread (bps), Volume (bid/ask stacked), 1m CVD, Buy vs Sell, Trade Pressure
  - Per-Minute Returns (green/red bars), 1m Close Price, Trade Stats (count + avg/max size)
  - Trade Flow scatter, Realized Volatility, Running Cumulative CVD (green/red segments), Buy/Sell Ratio (y=1.0 ref line)
  - VPIN Time Series (orange, 0.3/0.6 threshold lines)
  - Depth Heatmap (canvas, 5s refresh)
- Spread Distribution histogram + hourly heatmap (30s refresh)
- Cross-Metric Correlations 2x2 scatter grid (OI vs Price, Funding vs Premium, Volume vs Volatility, Imbalance vs Price), 30s refresh
- Large Trades live feed table (with flash animation for new trades)
- Orderbook Walls live feed table (side, price, qty, size multiple, status)
- VPIN card color-coded: green (<0.3), amber (0.3-0.6), red (>0.6)

### Futures (Live, 1s refresh)
- 6 live cards: Funding Rate, Mark Price, Index Price, OI, Spot-Futures Premium, Annualized Funding
- 6 Chart.js charts: Funding Rate, Mark vs Index Price (dual-line), OI Trend, OI Delta (green/red bars), Spot-Futures Premium, Liquidation Volume (buy/sell notional)
- Liquidations feed table (with notional column)

### Klines
- TradingView Lightweight Charts candlestick + volume overlay (volume colored by taker buy/sell ratio)
- Interval tab selector (1m/5m/15m/1h)
- Technical indicator toggle pills: EMA 20/50 (amber/violet overlays), Bollinger Bands (blue dashed), RSI (separate 150px pane with 70/30 lines), MACD (separate 180px pane: line/signal/histogram), Taker Buy/Sell (separate 150px histogram pane)
- Crosshair sync across all panes
- OHLCV data table, paginated
- Quote volume, trade count, taker buy/sell volume in data

### Common UI Patterns
- All pages have pill nav linking to all pages
- AJAX polling via fetch() + setInterval — no page reload, DOM updated in-place
- JSON data endpoints: `/data` suffix on live-updating pages
- Glass card styling: `bg-white/[0.03] border border-white/[0.06] rounded-xl backdrop-blur-sm`
- Accent bars on metric cards via `accent-bar-cyan` / `accent-bar-violet` classes

## Directory Structure
```
app/
├── Console/
│   └── Commands/        # 7 artisan commands
├── Contracts/
│   ├── Repositories/    # 6 repository interfaces
│   └── Services/        # 15 service interfaces
├── DTOs/
│   ├── Filters/         # 5 filter DTOs (DateRange, History, Analytics, Futures, Kline)
│   ├── Analytics/       # 5 response DTOs (ChartData, DepthHeatmap, Distribution, MarketRegime, Correlation)
│   ├── Dashboard/       # 1 response DTO (SummaryData)
│   ├── Futures/         # 1 response DTO (ChartData)
│   └── Orderbook/       # 1 response DTO (ViewData)
├── Http/
│   ├── Controllers/Admin/  # 7 focused controllers
│   └── Requests/Admin/     # 5 form request validators
├── Models/              # 15 Eloquent models (all with HasFactory + scopes)
├── Providers/           # AppServiceProvider + RepositoryServiceProvider
├── Repositories/        # 6 repository implementations
└── Services/
    ├── Ingestion/       # 9 ingestion services
    ├── Web/             # 6 web query services
    ├── BinanceWebSocketService.php
    └── BinanceFuturesWebSocketService.php
database/
└── factories/           # 15 model factories (+ UserFactory)
tests/
├── Feature/Admin/       # 15 feature test files
├── Unit/Services/       # 4 unit test files
├── Traits/              # CreatesTestData helper trait
└── TestCase.php         # Base test case with RefreshDatabase
```
