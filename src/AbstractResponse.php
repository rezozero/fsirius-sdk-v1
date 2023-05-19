<?php

namespace RZ\FSirius;

abstract class AbstractResponse
{
    /**
     * @return string
     */
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
        return isset($this->params['statut']) && $this->params['statut'] == 'ok';
    }

    /**
     * @return string|bool
     */
    public function getError(): string|bool
    {
        return $this->params['erreur'] ?? false;
    }

    /**
     * @param string $paramName
     * @return mixed|null
     */
    public function getParam(string $paramName): mixed
    {
        return $this->params[$paramName] ?? null;
    }

    /**
     * is utilized for reading data from inaccessible members.
     *
     * @param string $name
     *
     * @return mixed
     * @link https://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __get(string $name): mixed
    {
        return $this->getParam($name);
    }
}
