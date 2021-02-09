<?php


namespace App\Exceptions\ExternalServices\Google;

use RuntimeException;

class ActionDeterminationException extends RuntimeException
{
    public function __construct($message = "Unknown action")
    {
        parent::__construct($message);
    }
}
