<?php

namespace App\Exceptions;

use Exception;

class RateLimitExceededException extends Exception
{
    public function __construct($message = 'Limite de taux atteinte', $code = 429, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
