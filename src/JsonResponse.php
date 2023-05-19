<?php

namespace RZ\FSirius;

use Psr\Http\Message\ResponseInterface;

class JsonResponse extends AbstractResponse
{
    private ResponseInterface $response;
    private string $body;

    /**
     * @return string
     */
    public static function getContentType(): string
    {
        return 'application/json';
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->body = $response->getBody()->getContents();
        $this->response = $response;

        $this->params = json_decode($this->body, true);
    }
}
