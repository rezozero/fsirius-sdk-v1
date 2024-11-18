<?php

declare(strict_types=1);

namespace RZ\FSirius;

use Psr\Http\Message\ResponseInterface;

class TextResponse extends AbstractResponse
{
    private ResponseInterface $response;

    private string $body;

    public static function getContentType(): string
    {
        return 'text/plain';
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function __construct(ResponseInterface $response)
    {
        $this->body = $response->getBody()->getContents();
        $this->response = $response;

        parse_str($this->body, $this->params);
    }
}
