<?php

namespace RZ\FSirius;

use Psr\Http\Message\ResponseInterface;

class TextResponse extends AbstractResponse
{
    private ResponseInterface $response;

    private string $body;

    /**
     * @return string
     */
    public static function getContentType(): string
    {
        return 'text/plain';
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

        parse_str($this->body, $this->params);
    }
}
