<?php

namespace App\DTOs\Filters;

/**
 * Immutable filter DTO for futures page with separate history and OI date ranges.
 *
 * Supports independent filtering of the futures metric history table
 * and the open interest history table on the futures page.
 *
 * @property-read string|null $historyFrom  Start date filter for futures metric history table.
 * @property-read string|null $historyTo  End date filter for futures metric history table.
 * @property-read string|null $oiFrom  Start date filter for open interest table.
 * @property-read string|null $oiTo  End date filter for open interest table.
 */
readonly class FuturesFilter
{
    public function __construct(
        public ?string $historyFrom = null,
        public ?string $historyTo = null,
        public ?string $oiFrom = null,
        public ?string $oiTo = null,
    ) {}
}
