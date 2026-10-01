<?php

namespace App\Exceptions;

use RuntimeException;

class ReservationConflictException extends RuntimeException
{
    public function __construct(
        string $message = 'Selected slot unavailable.',
        public readonly array $conflicts = [],
    ) {
        parent::__construct($message);
    }
}
