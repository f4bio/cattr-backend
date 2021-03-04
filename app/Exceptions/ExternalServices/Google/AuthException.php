<?php


namespace App\Exceptions\ExternalServices\Google;

use RuntimeException;
use Throwable;

/**
 * @codeCoverageIgnore
 */
class AuthException extends RuntimeException
{
    private string $authUrl;

    public function __construct(
        string $authUrl,
        $message = 'Need auth via Google',
        Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->authUrl = $authUrl;
    }

    public function getAuthUrl(): string
    {
        return $this->authUrl;
    }
}
