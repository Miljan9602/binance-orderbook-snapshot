<?php

namespace App\DTOs\Filters;

use Illuminate\Http\Request;

/**
 * Immutable filter DTO for date range queries.
 *
 * Base filter class providing from/to date range fields.
 * Can be constructed directly or via the fromRequest() factory method.
 *
 * @property-read string|null $from  Start date filter (inclusive).
 * @property-read string|null $to  End date filter (inclusive).
 */
readonly class DateRangeFilter
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
    ) {}

    /**
     * Create a filter from a request using 'from' and 'to' parameters.
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
        );
    }
}
