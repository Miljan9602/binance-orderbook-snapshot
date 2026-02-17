<?php

return [
    'ws_base_url' => env('BINANCE_WS_BASE_URL', 'wss://stream.binance.com:9443'),
    'depth_level' => (int) env('BINANCE_DEPTH_LEVEL', 20),
    'reconnect_base_delay' => (int) env('BINANCE_RECONNECT_BASE_DELAY', 5),
    'reconnect_max_delay' => (int) env('BINANCE_RECONNECT_MAX_DELAY', 60),
    'history_retention_hours' => (int) env('BINANCE_HISTORY_RETENTION_HOURS', 24),
    'metrics_sample_interval' => (int) env('BINANCE_METRICS_SAMPLE_INTERVAL', 22),
    'futures_ws_base_url' => env('BINANCE_FUTURES_WS_BASE_URL', 'wss://fstream.binance.com'),
    'futures_rest_base_url' => env('BINANCE_FUTURES_REST_BASE_URL', 'https://fapi.binance.com'),
    'futures_open_interest_interval' => (int) env('BINANCE_FUTURES_OI_INTERVAL', 30),

    'large_trade_multiplier' => (float) env('BINANCE_LARGE_TRADE_MULTIPLIER', 3),
    'large_trade_window' => (int) env('BINANCE_LARGE_TRADE_WINDOW', 100),

    'wall_detection_multiplier' => (float) env('BINANCE_WALL_DETECTION_MULTIPLIER', 3),

    'regime_cvd_threshold' => (float) env('BINANCE_REGIME_CVD_THRESHOLD', 500),
    'regime_volatility_threshold' => (float) env('BINANCE_REGIME_VOLATILITY_THRESHOLD', 0.02),
    'regime_spread_threshold' => (float) env('BINANCE_REGIME_SPREAD_THRESHOLD', 10),
    'regime_large_trade_threshold' => (int) env('BINANCE_REGIME_LARGE_TRADE_THRESHOLD', 3),
    'regime_lookback' => (int) env('BINANCE_REGIME_LOOKBACK', 10),

    'vpin_bucket_count' => (int) env('BINANCE_VPIN_BUCKET_COUNT', 20),
    'vpin_window' => (int) env('BINANCE_VPIN_WINDOW', 10),
];
