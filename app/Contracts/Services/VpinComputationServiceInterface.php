<?php

namespace App\Contracts\Services;

/**
 * Contract for computing Volume-Synchronized Probability of Informed Trading (VPIN).
 *
 * Processes recent trades into volume-synchronized buckets and computes
 * VPIN as the average order imbalance across a sliding window of buckets.
 */
interface VpinComputationServiceInterface
{
    /**
     * Compute VPIN for all active trading pairs.
     */
    public function computeAll(): void;

    /**
     * Compute VPIN for a specific trading pair.
     */
    public function compute(int $tradingPairId): void;
}
