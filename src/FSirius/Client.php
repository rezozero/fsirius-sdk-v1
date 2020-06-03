<?php
namespace RZ\FSirius;

use Doctrine\Common\Cache\CacheProvider;

class Client
{
    const SESSION_ID = 'fsirius_sdk_session';
    const CACHE_KEY_DOMAIN = 'fsirius_sdk_token';
    const CACHE_TTL = 120;

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
     * @var CacheProvider|null
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
     * @var string
     */
    private $responseType = JsonResponse::class;

    /**
     * Client constructor.
     *
     * @param string $endpoint
     * @param string $clientId
     * @param CacheProvider|null $cacheProvider
     * @param string $responseType
     * @param string|null $proxy
     */
    public function __construct(
        string $endpoint,
        string $clientId,
        CacheProvider $cacheProvider = null,
        $responseType = JsonResponse::class,
        string $proxy = null
    ) {
        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Endpoint ' . $endpoint . ' must be a valid URL');
        }

        $this->endpoint = $endpoint;
        $this->responseType = $responseType;
        $config = [
            'base_uri' => $endpoint,
            'headers' => [
                'Accept' => call_user_func([$this->responseType, 'getContentType']),
                'X-Origin' => 'RZ-FSirius-SDK',
            ],
            'timeout' => 3,
            'allow_redirects' => [
                'max'       => 3,       // allow at most 10 redirects.
                'strict'    => true,     // use "strict" RFC compliant redirects.
                'referer'   => true,     // add a Referer header
                'protocols' => ['http', 'https'] // only allow https URLs
            ]
        ];
        if ($proxy !== null) {
            $config['proxy'] = $proxy;
        }

        $this->guzzleClient = new \GuzzleHttp\Client($config);
        $this->cacheProvider = $cacheProvider;
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getResponseType(): string
    {
        return $this->responseType;
    }

    /**
     * @return $this
     */
    public function setTextResponseType()
    {
        $this->responseType = TextResponse::class;
        return $this;
    }

    /**
     * @return $this
     */
    public function setJsonResponseType()
    {
        $this->responseType = JsonResponse::class;
        return $this;
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
        $responseType = $this->getResponseType();
        return new $responseType($this->getGuzzleClient()->get($url, $options));
    }

    /**
     * @param string $eventId
     * @return string
     */
    protected function getEventQuery($eventId)
    {
        return 'spec='.$eventId;
    }

    /**
     * Get valid season items.
     *
     * @param array $options
     * @return string|null
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
                'defSC' => $this->getEventQuery($eventId),
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
        $infosSC = $this->get('/ParamSC', [
            'query' => [
                'instPA' => $sessionToken,
                'defSC' => $this->getEventQuery($eventId),
            ]
        ])->getParam('infosSC');

        if (isset($infosSC['apiParamSC'])) {
            return $infosSC['apiParamSC'];
        }

        return [];
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
                'defSC' => $this->getEventQuery($eventId),
            ]
        ]);

        $eventDates = [];
        /*
         * For the moment listeSC is still a imploded array
         */
        $eventDateIds = explode(',', $eventDatesResponse->getParam('listeSC'));
        $eventCategories = explode(',', $eventDatesResponse->getParam('listeCat'));
        $eventCategoriesCount = count($eventCategories);
        /*
         * dispoVOR is used for each SC AND Cat
         */
        $eventDateAvailability = str_split($eventDatesResponse->getParam('dispoVOR'));

        if (count($eventDateAvailability) > 0) {
            foreach ($eventDateIds as $i => $eventDateId) {
                $eventAvailabilities = [];
                foreach ($eventCategories as $j => $eventCategory) {
                    $index = ($i*$eventCategoriesCount)+$j;
                    if (isset($eventDateAvailability[$index])) {
                        $eventAvailabilities[] = $eventDateAvailability[$index];
                    }
                }
                $eventDates[$eventDateId] = $this->getBestAvailabilities($eventAvailabilities);
            }
        }

        return $eventDates;
    }

    /**
     * Process availabilities with medium calculus.
     *
     * @param array $availabilities
     * @return string
     */
    protected function getMediumAvailabilities(array $availabilities = [])
    {
        if (count($availabilities) > 0) {
            $numericDispo = [
                static::FORBIDDEN_EVENT_DATE => 0,
                static::AVAILABLE_SEATS => 1,
                static::LATEST_SEATS => 2,
                static::NO_MORE_SEATS => 3,
            ];
            $numericDispoKeys = array_keys($numericDispo);
            $mediumDispo = 0.0;

            foreach ($availabilities as $availability) {
                if (isset($numericDispo[$availability])) {
                    $mediumDispo += $numericDispo[$availability];
                }
            }

            $mediumDispo /= count($availabilities);
            return (string) $numericDispoKeys[(int) floor($mediumDispo)];
        }

        return static::NO_MORE_SEATS;
    }

    /**
     * Process availabilities with at least one of the best availability found.
     *
     * @param array $availabilities
     * @return string
     */
    protected function getBestAvailabilities(array $availabilities = [])
    {
        if (count($availabilities) > 0) {
            if (in_array(static::AVAILABLE_SEATS, $availabilities)) {
                return static::AVAILABLE_SEATS;
            }
            if (in_array(static::LATEST_SEATS, $availabilities)) {
                return static::LATEST_SEATS;
            }
            return static::NO_MORE_SEATS;
        }

        return static::UNAVAILABLE_INFO;
    }

    /**
     * @param string $sessionToken
     * @param string $eventId
     *
     * @return EventDate[]
     * @throws \Exception
     */
    public function getEventDates($sessionToken, $eventId)
    {
        $eventDates = [];
        $eventDatesArray = $this->getEventDateParams($sessionToken, $eventId);
        $eventDatesAvailability = $this->getEventDateAvailability($sessionToken, $eventId);

        foreach ($eventDatesArray as $eventDateArray) {
            $eventDate = new EventDate($eventDateArray);
            /*
             * Set event-date availability only if we got an availability for a given sessionId.
             */
            if ("" !== $eventDate->getId() && isset($eventDatesAvailability[$eventDate->getId()])) {
                $eventDate->setAvailability($eventDatesAvailability[$eventDate->getId()]);
            }
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
