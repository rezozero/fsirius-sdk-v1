<?php

namespace RZ\FSirius;

use Doctrine\Common\Cache\CacheProvider;

class Client
{
    const SESSION_ID = 'fsirius_sdk_session';
    const CACHE_KEY_DOMAIN = 'fsirius_sdk_token';
    const CACHE_TTL = 3500;

    const AVAILABLE_SEATS = 'V';
    const LATEST_SEATS = 'O';
    const NO_MORE_SEATS = 'R';
    const FORBIDDEN_EVENT_DATE = '-';
    const UNAVAILABLE_INFO = '?';

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $guzzleClient;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $eventId;

    /**
     * Client constructor.
     * @param $endpoint
     * @param $clientId
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct($endpoint, $clientId, CacheProvider $cacheProvider = null)
    {
        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Endpoint must be a valid URL');
        }

        $this->endpoint = $endpoint;

        $this->guzzleClient = new \GuzzleHttp\Client([
            'base_url' => $endpoint,
            'defaults' => [
                'headers' => [
                    'Accept' => 'text/plain',
                    'Content-Type' => 'text/plain',
                    'X-Origin' => 'RZ-FSirius-SDK',
                ],
                'timeout' => 3,
                'allow_redirects' => [
                    'max'       => 3,       // allow at most 10 redirects.
                    'strict'    => true,     // use "strict" RFC compliant redirects.
                    'referer'   => true,     // add a Referer header
                    'protocols' => ['http', 'https'] // only allow https URLs
                ]
            ]
        ]);

        $this->cacheProvider = $cacheProvider;
        $this->clientId = $clientId;
    }

    /**
     * @return CacheProvider|null
     */
    public function getCacheProvider()
    {
        return $this->cacheProvider;
    }

    /**
     * @param CacheProvider $cacheProvider
     * @return Client
     */
    public function setCacheProvider(CacheProvider $cacheProvider): Client
    {
        $this->cacheProvider = $cacheProvider;
        return $this;
    }

    /**
     * @return string
     */
    public function getCacheKey()
    {
        return sha1(static::CACHE_KEY_DOMAIN . $this->eventId . $this->clientId);
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getGuzzleClient()
    {
        return $this->guzzleClient;
    }

    /**
     * GET request with no credentials.
     *
     * @param string $url
     * @param array $options
     * @return TextResponse
     */
    public function get($url, $options = [])
    {
        return new TextResponse($this->getGuzzleClient()->get($url, $options));
    }

    /**
     * Get valid season items.
     *
     * @param array $options
     * @return string
     */
    public function getSessionToken($options = [])
    {
        $options['spec'] = $this->getEventId();
        $compiledOptions = http_build_query($options);

        return $this->get('/Contexte', [
            'query' => [
                'inst' => $this->clientId,
                'session' => static::SESSION_ID,
                'paramsURL' => $compiledOptions,
            ]
        ])->getSessionToken();
    }

    /**
     * @param string $sessionToken
     * @param string $eventId
     * @return array
     */
    public function getEventDateIds($sessionToken, $eventId)
    {
        $eventDateIds = $this->get('/ListeSC', [
            'query' => [
                'instPA' => $sessionToken,
                'defSC' => 'SP='.$eventId,
            ]
        ])->getParam('listeSC');

        return explode(',', $eventDateIds);
    }

    /**
     * @param string $sessionToken
     * @param string $eventId
     * @return array
     */
    public function getEventDateParams($sessionToken, $eventId)
    {
        $eventDateJSCode = $this->get('/ParamSC', [
            'query' => [
                'instPA' => $sessionToken,
                'defSC' => 'SP='.$eventId,
            ]
        ])->getParam('infosSC');

        $eventDateJson = str_replace('var apiParamSC = ', '', trim($eventDateJSCode));
        $eventDateJson = preg_replace('#([\{|,])([a-zA-Z]+)\:#', '$1"$2":', $eventDateJson);

        return json_decode($eventDateJson, true);
    }

    /**
     * @param string $sessionToken
     * @param string $eventId
     * @return array
     */
    public function getEventDateAvailability($sessionToken, $eventId)
    {
        $eventDatesResponse = $this->get('/DispoListeSC', [
            'query' => [
                'instPA' => $sessionToken,
                'defSC' => 'SP='.$eventId,
            ]
        ]);

        $eventDates = [];
        $eventDateIds = explode(',', $eventDatesResponse->getParam('listeSC'));
        $eventDateAvailability = explode('', $eventDatesResponse->getParam('dispoVOR'));

        foreach ($eventDateIds as $i => $eventDateId) {
            $eventDates[$eventDateId] = $eventDateAvailability[$i];
        }

        return $eventDates;
    }

    /**
     * TODO: DispoListeSC is not available on API
     *
     * @param string $sessionToken
     * @param string $eventId
     * @return EventDate[]
     */
    public function getEventDates($sessionToken, $eventId)
    {
        $eventDates = [];
        $eventDatesArray = $this->getEventDateParams($sessionToken, $eventId);
        //$eventDatesAvailability = $this->getEventDateAvailability($sessionToken, $eventId);
        foreach ($eventDatesArray as $eventDateArray) {
            $eventDate = new EventDate($eventDateArray);
            //$eventDate->setAvailability($eventDatesAvailability[$eventDate->getId()]);
            $eventDates[] = $eventDate;
        }

        return $eventDates;
    }

    /**
     * @return mixed
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @param mixed $eventId
     * @return Client
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
        return $this;
    }
}
