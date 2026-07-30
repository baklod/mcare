<?php

namespace App\Exceptions;

use RuntimeException;

class PayMongoCheckoutException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable = false,
        public readonly ?int $responseStatus = null,
    ) {
        parent::__construct($message);
    }
}
