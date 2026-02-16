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
Binance WS -> BinanceWebSocketService -> OrderbookService -> Database -> Blade views (auto-refresh 300ms)

### WebSocket Streams (combined connection)
- `seiusdc@depth20` — Top 20 bid/ask levels (orderbook)
- `seiusdc@aggTrade` — Aggregated trades (price, qty, buy/sell)
- `seiusdc@kline_1m` — 1-minute OHLCV candles
- `seiusdc@ticker` — 24hr rolling stats

### Database Tables
- `trading_pairs` — Symbol config (SEIUSDC), active flag, depth level
- `orderbook_snapshots` — Latest orderbook state (1 row per pair, upserted)
- `orderbook_history` — Historical orderbook snapshots (append-only, cleaned hourly)
- `trades` — Aggregated trades (append-only, cleaned hourly)
- `tickers` — 24hr ticker stats (1 row per pair, upserted)
- `klines` — Candlestick data (upserted while open, kept after close, old closed cleaned hourly)

### Models
- `TradingPair` — has: snapshot (HasOne), history (HasMany), trades (HasMany), ticker (HasOne), klines (HasMany)
- `OrderbookSnapshot` — current orderbook state per pair
- `OrderbookHistory` — historical orderbook records
- `Trade` — aggregated trade records (timestamps managed manually, not via Eloquent)
- `Ticker` — 24hr rolling ticker stats
- `Kline` — candlestick/OHLCV data

### Services
- `OrderbookService` — `updateOrderbook()`, `saveTrade()`, `updateTicker()`, `updateKline()`, `cleanOldHistory()`
- `BinanceWebSocketService` — WebSocket connection management, message routing by stream type suffix (`@depth20`, `@aggTrade`, `@kline_1m`, `@ticker`)

### Key Files
- `app/Services/BinanceWebSocketService.php` — WebSocket client, stream routing
- `app/Services/OrderbookService.php` — All data persistence logic
- `app/Console/Commands/BinanceWebSocket.php` — `php artisan binance:websocket`
- `app/Console/Commands/CleanOrderbookHistory.php` — `php artisan orderbook:clean-history`
- `app/Http/Controllers/Admin/TradingPairController.php` — Dashboard + orderbook views
- `resources/views/admin/trading-pairs/index.blade.php` — Dashboard (price, 24h stats, cards)
- `resources/views/admin/trading-pairs/show.blade.php` — Professional 3-column orderbook
- `config/binance.php` — WS URL, depth level, reconnect delays, retention hours
- `database/seeders/TradingPairSeeder.php` — Seeds SEIUSDC pair
- `routes/console.php` — Hourly cleanup schedule

## Commands
```bash
php artisan binance:websocket    # Start WebSocket client (long-running)
php artisan orderbook:clean-history  # Clean old history/trades/klines
php artisan db:seed --class=TradingPairSeeder  # Seed trading pair
php artisan migrate              # Run migrations
php artisan serve                # Start web server
php artisan schedule:work        # Run scheduler (hourly cleanup)
```

## Routes
- `GET /` — Redirects to dashboard
- `GET /admin` — Redirects to dashboard
- `GET /admin/trading-pairs` — Dashboard with price, stats, cards
- `GET /admin/trading-pairs/data` — JSON API for dashboard (AJAX polling)
- `GET /admin/trading-pairs/{id}` — Professional orderbook (3-column: stats | book | trades)
- `GET /admin/trading-pairs/{id}/data` — JSON API for orderbook page (AJAX polling)
- `POST /admin/trading-pairs/{id}/toggle` — Activate/deactivate streaming

## Environment Variables (config/binance.php)
- `BINANCE_WS_BASE_URL` — default: `wss://stream.binance.com:9443`
- `BINANCE_DEPTH_LEVEL` — default: `20`
- `BINANCE_RECONNECT_BASE_DELAY` — default: `5` (seconds)
- `BINANCE_RECONNECT_MAX_DELAY` — default: `60` (seconds)
- `BINANCE_HISTORY_RETENTION_HOURS` — default: `24`

## UI Notes
- Dark theme (gray-950 bg), monospace tabular-nums for all prices
- Dashboard: hero price, 24h change, stat cards, ticker stats
- Orderbook: asks reversed (lowest near spread), depth bars (CSS width %), cumulative totals, spread/mid-price row, imbalance bar
- Recent trades: color-coded green (buy) / red (sell) based on `is_buyer_maker`
- AJAX polling via fetch() + setInterval at 300ms — no page reload, DOM updated in-place
- JSON data endpoints: `/data` suffix on each page route
