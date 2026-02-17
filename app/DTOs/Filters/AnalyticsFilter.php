<?php

namespace App\DTOs\Filters;

/**
 * Immutable filter DTO for analytics page with separate metric and aggregate date ranges.
 *
 * Supports independent filtering of the metrics table and the trade
 * aggregates table on the analytics page.
 *
 * @property-read string|null $metricsFrom  Start date filter for orderbook metrics table.
 * @property-read string|null $metricsTo  End date filter for orderbook metrics table.
 * @property-read string|null $aggFrom  Start date filter for trade aggregates table.
 * @property-read string|null $aggTo  End date filter for trade aggregates table.
 */
readonly class AnalyticsFilter
{
    public function __construct(
        public ?string $metricsFrom = null,
        public ?string $metricsTo = null,
        public ?string $aggFrom = null,
        public ?string $aggTo = null,
    ) {}
}
