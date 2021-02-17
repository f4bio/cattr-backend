<?php


namespace App\Services\External\Google;

use App\Models\Property;
use GuzzleHttp\ClientInterface;
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
     * @param string $actionId
     * @param array $state
     * @return string
     * @throws RuntimeException
     */
    public function getUrlByActionId(string $actionId, array $state = []): string
    {
        $state['instanceId'] = $this->instanceId;

        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf(
                    "%s/api/v1/actions/%s/url?%s",
                    config('app.google_integration_bus.url'),
                    $actionId,
                    http_build_query([
                        'instanceId' => $this->instanceId,
                        'state' => base64_encode(json_encode($state, JSON_THROW_ON_ERROR)),
                    ])
                )
            );

            $content = $response->getBody()->getContents();

            if ($response->getStatusCode() === Response::HTTP_OK) {
                $decodedResponse = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

                return $decodedResponse['url'];
            }

            throw new RuntimeException(sprintf(
                "Operation getting auth url by action id was failed. HTTP Status code: %s, content: %s",
                $response->getStatusCode(),
                $content
            ));
        } catch (Throwable $throwable) {
            $this->logger->alert(sprintf("%s%s%s", $throwable->getMessage(), PHP_EOL, $throwable->getTraceAsString()));

            throw new RuntimeException('Operation getting auth url by action id was failed', 0, $throwable);
        }
    }
}
