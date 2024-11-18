<?php

declare(strict_types=1);

namespace RZ\FSirius;

abstract class AbstractResponse
{
    abstract public static function getContentType(): string;

    /**
     * @var array<string, mixed>
     */
    protected array $params = [];

    public function getSessionToken(): ?string
    {
        return $this->params['instPA'] ?? null;
    }

    public function isStatusOk(): bool
    {
        return isset($this->params['statut']) && 'ok' == $this->params['statut'];
    }

    public function getError(): string|bool
    {
        return $this->params['erreur'] ?? false;
    }

    /**
     * @return mixed|null
     */
    public function getParam(string $paramName): mixed
    {
        return $this->params[$paramName] ?? null;
    }

    /**
     * is utilized for reading data from inaccessible members.
     *
     * @see https://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __get(string $name): mixed
    {
        return $this->getParam($name);
    }
}
