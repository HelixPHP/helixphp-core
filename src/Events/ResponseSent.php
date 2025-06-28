<?php

declare(strict_types=1);

namespace Express\Events;

/**
 * Response Sent Event
 */
class ResponseSent
{
    public function __construct(
        public readonly mixed $request,
        public readonly mixed $response,
        public readonly \DateTime $sentAt,
        public readonly float $processingTime
    ) {
    }
}
