<?php
namespace RZ\FSirius;

abstract class AbstractResponse
{
    /**
     * @return string
     */
    abstract public static function getContentType(): string;

    /**
     * @var array
     */
    protected $params;

    /**
     * @return string|null
     */
    public function getSessionToken()
    {
        return isset($this->params['instPA']) ? $this->params['instPA'] : null;
    }

    /**
     * @return boolean
     */
    public function isStatusOk()
    {
        return isset($this->params['statut']) && $this->params['statut'] == 'ok' ? true : false;
    }

    /**
     * @return string|bool
     */
    public function getError()
    {
        return isset($this->params['erreur']) ? $this->params['erreur'] : false;
    }

    /**
     * @param $paramName
     * @return array|mixed|null
     */
    public function getParam($paramName)
    {
        return isset($this->params[$paramName]) ? $this->params[$paramName] : null;
    }
}
