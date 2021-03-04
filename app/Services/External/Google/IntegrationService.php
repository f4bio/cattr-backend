<?php


namespace App\Services\External\Google;

use App\Exceptions\ExternalServices\Google\AuthException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class IntegrationService
{
    private ClientInterface $httpClient;
    private LoggerInterface $logger;

    /**
     * IntegrationService constructor.
     * @param ClientInterface $httpClient
     * @param LoggerInterface $logger
     * @throws RuntimeException
     */
    public function __construct(ClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * @param array $state
     * @return void
     * @throws AuthException
     * @throws RuntimeException
     */
    public function auth(array $state): void
    {
        $this->logger->debug('The system is going to send a request to check auth to export a report in Google Sheet.');

        try {
            $this->tryAuth($state);
        } catch (ClientException $clientException) {
            $this->handleClientExceptionsForAuth($clientException);
        } catch (Throwable $throwable) {
            $this->logger->alert(sprintf("%s%s%s", $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));

            throw new RuntimeException('Operation check access to export was failed', 0, $throwable);
        }
    }

    /**
     * @param array $state
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws JsonException
     */
    private function sendRequestAuth(array $state): ResponseInterface
    {
        return $this->httpClient->request(
            'POST',
            sprintf(
                "%s/api/v1/google/auth",
                config('app.google_integration_bus.url'),
            ),
            [
                RequestOptions::HEADERS => [
                    'Cattr-user-id' => $state['userId'],
                    'Cattr-instance-id' => $state['instanceId'],
                ],
                RequestOptions::JSON => [
                    'state' => base64_encode(json_encode($state, JSON_THROW_ON_ERROR))
                ]
            ]
        );
    }

    /**
     * @param array $state
     * @throws GuzzleException
     * @throws JsonException
     * @throws RuntimeException
     */
    private function tryAuth(array $state): void
    {
        $response = $this->sendRequestAuth($state);
        $content = $response->getBody()->getContents();

        $this->logger->debug(sprintf(
            "The system received response just now. Body: %s, Status: %s",
            $content,
            $response->getStatusCode()
        ));

        if ($response->getStatusCode() === Response::HTTP_NO_CONTENT) {
            $this->logger->debug(sprintf("User %d has access to export in Google Sheet", $state['userId']));
        }

        $this->logger->debug(sprintf(
            "Google Proxy service sent response with unknown status. Status: %s, Content: %s",
            $response->getStatusCode(),
            $content
        ));
    }

    /**
     * @param ClientException $clientException
     * @throws AuthException
     */
    private function handleClientExceptionsForAuth(ClientException $clientException): void
    {
        $response = $clientException->getResponse();
        $content = $response->getBody()->getContents();
        $this->logger->error(sprintf(
            "Client exception.%sStatus: %s Body: %s%s%s%s%s",
            PHP_EOL,
            $response->getStatusCode(),
            $content,
            PHP_EOL,
            $clientException->getMessage(),
            PHP_EOL,
            $clientException->getTraceAsString()
        ));

        if ($response->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
            $decodedResponse = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            throw new AuthException($decodedResponse['url']);
        }

        throw $clientException;
    }
}
