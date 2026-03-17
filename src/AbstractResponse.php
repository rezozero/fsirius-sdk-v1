<?php

declare(strict_types=1);

namespace RZ\FSirius;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractResponse
{
    abstract public static function getContentType(): string;

    /**
     * @var array<int|string, array|string>|null
     */
    protected ?array $params = null;

    public function __construct(protected readonly ResponseInterface $response)
    {
    }

    /**
     * @throws HttpExceptionInterface
     */
    public function getSessionToken(): ?string
    {
        return $this->getParam('instPA') ?? null;
    }

    /**
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function isStatusOk(): bool
    {
        return 200 === $this->response->getStatusCode() && 'ok' === $this->getParam('statut');
    }

    /**
     * @throws HttpExceptionInterface
     */
    public function getError(): string|bool
    {
        return $this->getParam('erreur') ?? false;
    }

    /**
     * is utilized for reading data from inaccessible members.
     *
     * @see https://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     *
     * @throws HttpExceptionInterface
     */
    public function __get(string $name): mixed
    {
        return $this->getParam($name);
    }

    /**
     * @throws HttpExceptionInterface
     */
    public function getParam(string $paramName): mixed
    {
        return $this->getParams()[$paramName] ?? null;
    }

    /**
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getBody(): string
    {
        return $this->response->getContent();
    }

    /**
     * @throws HttpExceptionInterface
     */
    public function getParams(): array
    {
        if (null === $this->params) {
            $this->parseParams();
        }

        return $this->params ?? [];
    }

    /**
     * @throws HttpExceptionInterface
     */
    abstract protected function parseParams(): void;
}
