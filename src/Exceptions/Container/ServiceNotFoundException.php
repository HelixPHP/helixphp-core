<?php

declare(strict_types=1);

namespace Helix\Exceptions\Container;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception thrown when a service is not found in the container
 */
class ServiceNotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
