<?php

namespace App\Exceptions;

use RuntimeException;

class RadiopipeTopicRatingException extends RuntimeException
{
    public function __construct(string $message, private readonly ?int $statusCode = null)
    {
        parent::__construct($message);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }
}
