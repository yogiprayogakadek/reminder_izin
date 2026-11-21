<?php

namespace App\Services\Waha;

use RuntimeException;

class WahaException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null)
    {
        parent::__construct($message);
    }
}
