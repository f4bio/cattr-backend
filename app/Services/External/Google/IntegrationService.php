<?php


namespace App\Services\External\Google;

use App\Exceptions\ExternalServices\Google\AuthException;
use App\Models\Property;
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
    private string $instanceId;

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
        $this->instanceId = Property::getInstanceId();
    }

    /**
     * @param int $userId
     * @param array $state
     * @return void
     * @throws AuthException
     * @throws RuntimeException
     */
    public function auth(int $userId, array $state = []): void
    {
        $state['instanceId'] = $this->instanceId;
        $state['domain'] = config('app.domain');
        $state['userId'] = $userId;
        $this->logger->debug('The system is going to send a request to check auth to export a report in Google Sheet.');

        try {
            $response = $this->sendRequestAuth($state, $userId);

            $this->logger->debug(sprintf(
                "The system received response just now. Body: %s, Status: %s",
                $response->getBody(),
                $response->getStatusCode()
            ));

            $this->logger->debug('Auth');

            if ($response->getStatusCode() === Response::HTTP_NO_CONTENT) {
                return;
            }

            throw new RuntimeException(sprintf(
                "Google Proxy service sent response with unknown status. Status: %s, Content: %s",
                $response->getStatusCode(),
                $response->getBody()->getContents()
            ));
        } catch (ClientException $clientException) {
            $response = $clientException->getResponse();

            if ($response->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
                $decodedResponse = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

                throw new AuthException($decodedResponse['url']);
            }
        } catch (Throwable $throwable) {
            $this->logger->alert(sprintf("%s%s%s", $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));

            throw new RuntimeException('Operation check access to export was failed', 0, $throwable);
        }
    }

    /**
     * @param array $state
     * @param int $userId
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws JsonException
     */
    private function sendRequestAuth(array $state, int $userId): ResponseInterface
    {
        return $this->httpClient->request(
            'POST',
            sprintf(
                "%s/api/v1/google/auth",
                config('app.google_integration_bus.url'),
            ),
            [
                RequestOptions::JSON => [
                    'state' => base64_encode(json_encode($state, JSON_THROW_ON_ERROR)),
                    'userId' => $userId,
                    'instanceId' => $this->instanceId,
                ]
            ]
        );
    }
}
