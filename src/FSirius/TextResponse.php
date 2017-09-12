<?php

namespace RZ\FSirius;

use GuzzleHttp\Message\ResponseInterface;

class TextResponse
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var string
     */
    private $body;
    /**
     * @var array
     */
    private $params;

    /**
     * JsonResponse constructor.
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->body = $response->getBody();
        $this->response = $response;

        parse_str($this->body, $this->params);
    }

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
