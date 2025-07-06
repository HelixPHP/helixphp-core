<?php

declare(strict_types=1);

namespace Express\Exceptions;

use Exception;

/**
 * Database Exception
 *
 * Thrown when database operations fail
 */
class DatabaseException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
