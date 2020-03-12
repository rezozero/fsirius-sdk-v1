<?php

namespace RZ\FSirius;

use Psr\Http\Message\ResponseInterface;

class JsonResponse extends AbstractResponse
{
    /**
     * @return string
     */
    public static function getContentType(): string
    {
        return 'application/json';
    }

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var string
     */
    private $body;

    /**
     * JsonResponse constructor.
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->body = $response->getBody();
        $this->response = $response;

        $this->params = json_decode($this->body, true);
    }
}
