<?php

declare(strict_types=1);

namespace PivotPHP\Core\Events;

/**
 * Request Received Event
 */
class RequestReceived
{
    public function __construct(
        public readonly mixed $request,
        public readonly \DateTime $receivedAt
    ) {
    }
}
