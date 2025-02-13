<?php

declare(strict_types=1);

namespace RZ\FSirius;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class JsonResponse extends AbstractResponse
{
    public static function getContentType(): string
    {
        return 'application/json';
    }

    public function __construct(ResponseInterface $response)
    {
        parent::__construct($response);
    }

    /**
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function parseParams(): void
    {
        $this->params = \json_decode($this->getBody(), true);
    }
}
