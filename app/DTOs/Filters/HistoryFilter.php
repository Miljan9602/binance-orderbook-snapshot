<?php

namespace App\DTOs\Filters;

use Illuminate\Http\Request;

/**
 * Immutable filter DTO for orderbook history queries with spread filtering.
 *
 * Extends the base date range filter with minimum and maximum spread
 * bounds for filtering orderbook history records.
 *
 * @property-read string|null $from  Start date filter (inherited).
 * @property-read string|null $to  End date filter (inherited).
 * @property-read float|null $minSpread  Minimum spread filter (inclusive).
 * @property-read float|null $maxSpread  Maximum spread filter (inclusive).
 */
readonly class HistoryFilter extends DateRangeFilter
{
    public function __construct(
        ?string $from = null,
        ?string $to = null,
        public ?float $minSpread = null,
        public ?float $maxSpread = null,
    ) {
        parent::__construct($from, $to);
    }

    /**
     * Create a history filter from a request.
     *
     * Extracts date range and spread bounds from the request inputs.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request containing filter inputs.
     * @param  string  $fromKey  The request input key for the start date.
     * @param  string  $toKey  The request input key for the end date.
     * @return static
     */
    public static function fromRequest(Request $request, string $fromKey = 'from', string $toKey = 'to'): static
    {
        return new static(
            from: $request->input($fromKey),
            to: $request->input($toKey),
            minSpread: $request->filled('min_spread') ? (float) $request->input('min_spread') : null,
            maxSpread: $request->filled('max_spread') ? (float) $request->input('max_spread') : null,
        );
    }
}
