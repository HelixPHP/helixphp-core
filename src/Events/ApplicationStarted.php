<?php

declare(strict_types=1);

namespace Helix\Events;

/**
 * Application Started Event
 */
class ApplicationStarted
{
    public function __construct(
        public readonly \DateTime $startTime,
        public readonly array $config = []
    ) {
    }
}
