<?php


namespace App\Exceptions\ExternalServices\Google\Sheets;

use App\Exceptions\ImproveException;
use Throwable;

class ExportException extends ImproveException
{
    public function __construct(
        $message = 'Failed Export in Google Sheets',
        Throwable $previous = null,
        array $invalidParams = [],
        array $additionalParams = []
    ) {
        parent::__construct($message, $previous, $invalidParams, $additionalParams);
    }
}
