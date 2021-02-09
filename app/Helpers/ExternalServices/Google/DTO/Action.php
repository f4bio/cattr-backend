<?php


namespace App\Helpers\ExternalServices\Google\DTO;

class Action
{
    private string $redirectUrl;
    private array $scopes;

    /**
     * Action constructor.
     * @param string $redirectUrl
     * @param array|string[] $scopes
     */
    public function __construct(string $redirectUrl, array $scopes)
    {
        $this->redirectUrl = $redirectUrl;
        $this->scopes = $scopes;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }
}
