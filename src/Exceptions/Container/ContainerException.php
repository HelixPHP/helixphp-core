<?php

declare(strict_types=1);

namespace Express\Exceptions\Container;

use Psr\Container\ContainerExceptionInterface;

/**
 * Exception thrown when an error occurs in the container
 */
class ContainerException extends \Exception implements ContainerExceptionInterface
{
}
