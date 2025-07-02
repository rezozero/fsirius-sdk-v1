<?php

declare(strict_types=1);

namespace RZ\FSirius;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class TextResponse extends AbstractResponse
{
    #[\Override]
    public static function getContentType(): string
    {
        return 'text/plain';
    }

    public function __construct(ResponseInterface $response)
    {
        parent::__construct($response);
    }

    /**
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
     */
    #[\Override]
    protected function parseParams(): void
    {
        parse_str($this->getBody(), $this->params);
    }
}
