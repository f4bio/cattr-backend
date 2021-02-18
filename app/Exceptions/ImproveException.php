<?php


namespace App\Exceptions;

use Exception;
use Throwable;

class ImproveException extends Exception
{
    private array $invalidParams;
    private array $additionalParams;

    public function __construct(
        $message = 'Application error',
        Throwable $previous = null,
        array $invalidParams = [],
        array $additionalParams = []
    ) {
        parent::__construct($message, 0, $previous);
        $this->invalidParams = $invalidParams;
        $this->additionalParams = $additionalParams;
    }

    public function getInvalidParams(): array
    {
        return $this->invalidParams;
    }

    public function getAdditionalParams(): array
    {
        return $this->additionalParams;
    }

    /**
     * @param string $message
     * @param array $invalidParams
     * @return static
     */
    public static function fromMessageAndInvalidParams(string $message, array $invalidParams)
    {
        return new static($message, null, $invalidParams);
    }

    public function normalizeToHttpResponseBody(): array
    {
        return [
            'additional_params' => $this->additionalParams,
            'invalid_params' => $this->invalidParams,
            'message' => $this->message,
        ];
    }
}
