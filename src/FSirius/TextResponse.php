<?php

namespace RZ\FSirius;

use Psr\Http\Message\ResponseInterface;

class TextResponse extends AbstractResponse
{
    /**
     * @return string
     */
    public static function getContentType(): string
    {
        return 'text/plain';
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

        parse_str($this->body, $this->params);
    }
}
