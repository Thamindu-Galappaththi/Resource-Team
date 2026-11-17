<?php

namespace App\Exceptions;

use RuntimeException;

class ReservationConflictException extends RuntimeException
{
    public function __construct(
        string $message = 'This slot is not available.',
        public readonly array $conflicts = [],
    ) {
        parent::__construct($message);
    }
}
