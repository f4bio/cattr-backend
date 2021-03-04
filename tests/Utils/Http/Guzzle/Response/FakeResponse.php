<?php


namespace Tests\Utils\Http\Guzzle\Response;

use Illuminate\Http\Response;
use JsonException;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class FakeResponse
{
    private string $content;
    private int $statusCode;

    public function __construct(
        string $content = '',
        int $statusCode = Response::HTTP_OK
    ) {
        $this->content = $content;
        $this->statusCode = $statusCode;
    }

    /**
     * @param array $body
     * @param int $statusCode
     * @return $this
     *
     * @throws JsonException
     */
    public static function createFromJsonEncodeBody(
        array $body = [],
        int $statusCode = Response::HTTP_OK
    ) {
        return new static(json_encode($body, JSON_THROW_ON_ERROR), $statusCode);
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|null|ResponseInterface
     */
    public function generate()
    {
        return Mockery::mock(ResponseInterface::class)
            ->shouldReceive([
                'getStatusCode' => $this->statusCode,
                'getBody' => Mockery::mock(StreamInterface::class)
                    ->shouldReceive(['getContents' => $this->content])
                    ->getMock(),
            ])->getMock();
    }
}
