<?php


namespace App\Helpers\ExternalServices\Google\DTO;

class Action
{
    private string $redirectUrl;
    private array $scopes;
    private ?string $state;

    /**
     * Action constructor.
     * @param string $redirectUrl
     * @param array|string[] $scopes
     * @param string|null $state
     */
    public function __construct(string $redirectUrl, array $scopes, ?string $state = null)
    {
        $this->redirectUrl = $redirectUrl;
        $this->scopes = $scopes;
        $this->state = $state;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getState(): ?string
    {
        return $this->state;
    }
}
