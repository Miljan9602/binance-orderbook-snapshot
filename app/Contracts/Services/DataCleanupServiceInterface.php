<?php

namespace App\Contracts\Services;

/**
 * Contract for cleaning old data based on retention policy.
 *
 * Provides methods for purging expired spot and futures data
 * according to the configured retention hours.
 */
interface DataCleanupServiceInterface
{
    /**
     * Clean old spot data based on retention configuration.
     *
     * @return array<string, int>  Counts of deleted records per table.
     */
    public function cleanSpotData(): array;

    /**
     * Clean old futures data based on retention configuration.
     *
     * @return array<string, int>  Counts of deleted records per table.
     */
    public function cleanFuturesData(): array;
}
