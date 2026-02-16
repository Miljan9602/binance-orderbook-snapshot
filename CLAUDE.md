# Binance Orderbook Monitor

## Project Overview
Laravel 12 app that streams real-time orderbook data from Binance WebSocket API and displays it in a professional exchange-style UI. Built for NIMA Research quantitative analysis.

## Tech Stack
- PHP 8.2+ / Laravel 12
- SQLite database (default) — can be swapped to MySQL/Postgres
- Tailwind CSS via CDN (no build step for styles)
- Ratchet/Pawl for WebSocket client
- ReactPHP event loop for long-running WebSocket process

## Architecture

### Data Flow
- Spot: Binance WS -> BinanceWebSocketService -> OrderbookService -> Database -> Blade views (auto-refresh 300ms)
- Futures: Binance Futures WS -> BinanceFuturesWebSocketService -> BinanceFuturesService -> Database -> Blade views (auto-refresh 1s)
- Computed: OrderbookService computes metrics inline (sampled) + scheduled trade aggregation

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

### Database Tables
- `trading_pairs` — Symbol config (SEIUSDC), active flag, depth level, futures_symbol
- `orderbook_snapshots` — Latest orderbook state (1 row per pair, upserted)
- `orderbook_history` — Historical orderbook snapshots (append-only, cleaned hourly)
- `trades` — Aggregated trades (append-only, cleaned hourly)
- `tickers` — 24hr ticker stats (1 row per pair, upserted)
- `klines` — Candlestick data for all intervals (upserted while open, old closed cleaned hourly)
- `orderbook_metrics` — Computed OB analytics: imbalance, VWAP mid, spread bps (append-only, sampled)
- `trade_aggregates` — 1m rollups: VWAP, CVD, buy/sell volume, trade stats
- `futures_metrics` — Latest mark price, funding rate (1 row per pair, upserted)
- `futures_metrics_history` — Historical futures metrics (append-only, sampled)
- `liquidations` — Force liquidation orders (append-only)
- `open_interest` — Polled OI snapshots (append-only)

### Models
- `TradingPair` — has: snapshot, history, trades, ticker, klines, orderbookMetrics, tradeAggregates, futuresMetric, futuresMetricsHistory, liquidations, openInterest
- `OrderbookSnapshot` — current orderbook state per pair
- `OrderbookHistory` — historical orderbook records
- `Trade` — aggregated trade records (timestamps managed manually)
- `Ticker` — 24hr rolling ticker stats
- `Kline` — candlestick/OHLCV data (all intervals)
- `OrderbookMetric` — computed orderbook analytics (sampled)
- `TradeAggregate` — periodic trade rollups
- `FuturesMetric` — latest futures data per pair
- `FuturesMetricHistory` — historical futures metrics
- `Liquidation` — forced liquidation orders
- `OpenInterest` — polled open interest data

### Services
- `OrderbookService` — `updateOrderbook()` (+ inline metrics), `saveTrade()`, `updateTicker()`, `updateKline()`, `computeTradeAggregates()`, `cleanOldHistory()`
- `BinanceWebSocketService` — Spot WebSocket connection, routes all kline_* variants via str_starts_with
- `BinanceFuturesService` — `updateMarkPrice()` (+ sampled history), `saveLiquidation()`, `fetchAndSaveOpenInterest()`, `cleanOldHistory()`
- `BinanceFuturesWebSocketService` — Futures WebSocket connection to fstream.binance.com

### Key Files
- `app/Services/BinanceWebSocketService.php` — Spot WebSocket client, stream routing
- `app/Services/BinanceFuturesWebSocketService.php` — Futures WebSocket client
- `app/Services/OrderbookService.php` — Spot data persistence + computed metrics
- `app/Services/BinanceFuturesService.php` — Futures data persistence + OI polling
- `app/Http/Controllers/Admin/TradingPairController.php` — All page controllers
- `config/binance.php` — WS URLs, depth level, reconnect delays, retention, metrics interval, futures config
- `database/seeders/TradingPairSeeder.php` — Seeds SEIUSDC pair with futures_symbol
- `routes/console.php` — Scheduling: cleanup hourly, trade aggregation every minute, OI every 30s

## Commands
```bash
php artisan binance:websocket         # Start spot WebSocket client (long-running)
php artisan binance:futures-websocket  # Start futures WebSocket client (long-running)
php artisan orderbook:clean-history    # Clean old spot history/trades/klines/metrics
php artisan binance:clean-futures-history  # Clean old futures history/liquidations/OI
php artisan trades:aggregate           # Aggregate trades from last minute
php artisan binance:fetch-open-interest  # Poll open interest from REST API
php artisan db:seed --class=TradingPairSeeder  # Seed trading pair
php artisan migrate                    # Run migrations
php artisan serve                      # Start web server
php artisan schedule:work              # Run scheduler
```

## Routes
- `GET /` — Redirects to dashboard
- `GET /admin` — Redirects to dashboard
- `GET /admin/trading-pairs` — Dashboard with price, stats, cards
- `GET /admin/trading-pairs/data` — JSON API for dashboard (AJAX polling)
- `GET /admin/trading-pairs/{id}` — Professional orderbook (3-column: stats | book | trades)
- `GET /admin/trading-pairs/{id}/data` — JSON API for orderbook page (AJAX polling)
- `GET /admin/trading-pairs/{id}/history` — Orderbook history (paginated, filterable)
- `GET /admin/trading-pairs/{id}/analytics` — Analytics page (metrics + trade aggregates)
- `GET /admin/trading-pairs/{id}/analytics/data` — JSON API for live analytics cards
- `GET /admin/trading-pairs/{id}/futures` — Futures page (mark price, liquidations, OI)
- `GET /admin/trading-pairs/{id}/futures/data` — JSON API for live futures cards + liquidations
- `GET /admin/trading-pairs/{id}/klines` — Multi-timeframe klines (1m/5m/15m/1h tabs)
- `POST /admin/trading-pairs/{id}/toggle` — Activate/deactivate streaming

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

## Procfile
```
web: php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
websocket: php artisan binance:websocket          # spot streams (7 streams)
futures: php artisan binance:futures-websocket     # futures streams (mark price, liquidations)
scheduler: php artisan schedule:work               # trade aggregation + OI polling + cleanup
```

## UI Notes
- Dark theme (gray-950 bg), monospace tabular-nums for all prices
- Dashboard: hero price, 24h change, stat cards, ticker stats, nav buttons to all pages
- Orderbook: asks reversed (lowest near spread), depth bars (CSS width %), cumulative totals, spread/mid-price row, imbalance bar
- Recent trades: color-coded green (buy) / red (sell) based on `is_buyer_maker`
- Analytics: live metrics cards (1s refresh), orderbook metrics history table, trade aggregates table (both paginated)
- Futures: live mark price/funding/OI cards (1s refresh), spot vs futures premium, live liquidations feed (2s), history tables (paginated)
- Klines: interval tab selector (1m/5m/15m/1h), OHLCV table with open candle highlighted, paginated
- All pages have breadcrumb nav linking to all other pages
- AJAX polling via fetch() + setInterval — no page reload, DOM updated in-place
- JSON data endpoints: `/data` suffix on live-updating pages
