<?php


namespace Tests\Utils\Http\Guzzle\Response\Exceptions;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Response;
use JsonException;
use Mockery;
use Tests\Utils\Http\Guzzle\Response\FakeResponse;

class ClientExceptionFaker
{
    private string $message;
    private string $content;
    private int $statusCode;

    public function __construct(
        string $message = '',
        string $content = '',
        int $statusCode = Response::HTTP_BAD_REQUEST
    ) {
        $this->message = $message;
        $this->content = $content;
        $this->statusCode = $statusCode;
    }

    /**
     * @param array $body
     * @param int $statusCode
     * @param string $message
     * @return $this
     *
     * @throws JsonException
     */
    public static function createFromJsonEncodeBody(
        array $body = [],
        int $statusCode = Response::HTTP_BAD_REQUEST,
        string $message = ''
    ) {
        return new static($message, json_encode($body, JSON_THROW_ON_ERROR), $statusCode);
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|null|ClientException
     */
    public function generate()
    {
        return Mockery::mock(ClientException::class)
            ->shouldReceive([
                'getResponse' => (new FakeResponse($this->content, $this->statusCode))->generate(),
                'getMessage' => $this->message,
                'getTraceAsString' => '',
            ])
            ->getMock();
    }
}
