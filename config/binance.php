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
];
