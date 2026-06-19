<?php

namespace App\Services\TripSplit;

use RuntimeException;

class InsufficientCreditsException extends RuntimeException
{
    public function __construct(private readonly int $usageCount)
    {
        parent::__construct('insufficient_credits');
    }

    public function usageCount(): int
    {
        return $this->usageCount;
    }
}
