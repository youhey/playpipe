<?php

namespace App\Services\Episodes;

use RuntimeException;

class DuplicateEpisodeException extends RuntimeException
{
    public function __construct(public readonly string $episodeKey)
    {
        parent::__construct('Episode already exists.');
    }
}
