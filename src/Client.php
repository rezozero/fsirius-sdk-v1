<?php

declare(strict_types=1);

namespace RZ\FSirius;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Exception\GuzzleException;

class Client
{
    public const SESSION_ID = 'fsirius_sdk_session';
    public const CACHE_KEY_DOMAIN = 'fsirius_sdk_token';
    public const AVAILABLE_SEATS = 'V';
    public const LATEST_SEATS = 'O';
    public const NO_MORE_SEATS = 'R';
    public const FORBIDDEN_EVENT_DATE = '-';
    public const UNAVAILABLE_INFO = '?';

    protected \GuzzleHttp\Client $guzzleClient;
    private ?CacheProvider $cacheProvider = null;
    private string $clientId;
    private ?string $eventId = null;
    /**
     * @var class-string<AbstractResponse>
     */
    private string $responseType = JsonResponse::class;

    /**
     * @param class-string<AbstractResponse> $responseType
     */
    public function __construct(
        string $endpoint,
        string $clientId,
        ?CacheProvider $cacheProvider = null,
        string $responseType = JsonResponse::class,
        ?string $proxy = null,
    ) {
        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Endpoint '.$endpoint.' must be a valid URL');
        }

        $this->responseType = $responseType;
        $config = [
            'base_uri' => $endpoint,
            'headers' => [
                'Accept' => call_user_func([$this->responseType, 'getContentType']),
                'X-Origin' => 'RZ-FSirius-SDK',
            ],
            'timeout' => 4,
            'connect_timeout' => 2,
            'allow_redirects' => [
                'max' => 3,       // allow at most 10 redirects.
                'strict' => true,     // use "strict" RFC compliant redirects.
                'referer' => true,     // add a Referer header
                'protocols' => ['http', 'https'], // only allow https URLs
            ],
        ];
        if (null !== $proxy) {
            $config['proxy'] = $proxy;
        }

        $this->guzzleClient = new \GuzzleHttp\Client($config);
        $this->cacheProvider = $cacheProvider;
        $this->clientId = $clientId;
    }

    /**
     * @return class-string<AbstractResponse>
     */
    public function getResponseType(): string
    {
        return $this->responseType;
    }

    /**
     * @return $this
     */
    public function setTextResponseType(): Client
    {
        $this->responseType = TextResponse::class;

        return $this;
    }

    /**
     * @return $this
     */
    public function setJsonResponseType(): Client
    {
        $this->responseType = JsonResponse::class;

        return $this;
    }

    public function getCacheProvider(): ?CacheProvider
    {
        return $this->cacheProvider;
    }

    public function setCacheProvider(CacheProvider $cacheProvider): Client
    {
        $this->cacheProvider = $cacheProvider;

        return $this;
    }

    public function getCacheKey(): string
    {
        return sha1(static::CACHE_KEY_DOMAIN.$this->eventId.$this->clientId);
    }

    public function getGuzzleClient(): \GuzzleHttp\Client
    {
        return $this->guzzleClient;
    }

    /**
     * GET request with no credentials.
     *
     * @throws GuzzleException
     */
    public function get(string $url, array $options = []): AbstractResponse
    {
        $responseType = $this->getResponseType();

        return new $responseType($this->getGuzzleClient()->get($url, $options));
    }

    protected function getEventQuery(string $eventId): string
    {
        return 'spec='.$eventId;
    }

    /**
     * Get valid season items.
     *
     * @throws GuzzleException
     */
    public function getSessionToken(array $options = []): ?string
    {
        $options['spec'] = $this->getEventId();
        $compiledOptions = http_build_query($options);

        return $this->get('/Contexte', [
            'query' => [
                'inst' => $this->clientId,
                'session' => $this->clientId.'_'.static::SESSION_ID,
                'paramsURL' => $compiledOptions,
            ],
        ])->getSessionToken();
    }

    /**
     * @throws GuzzleException
     */
    public function getEventDateIds(string $sessionToken, string $eventId): array
    {
        $eventDateIds = $this->get('/ListeSC', [
            'query' => [
                'instPA' => $sessionToken,
                'defSC' => $this->getEventQuery($eventId),
            ],
        ])->getParam('listeSC');

        return explode(',', $eventDateIds);
    }

    /**
     * @throws GuzzleException
     */
    public function getEventDateParams(string $sessionToken, string $eventId): array
    {
        $infosSC = $this->get('/ParamSC', [
            'query' => [
                'instPA' => $sessionToken,
                'defSC' => $this->getEventQuery($eventId),
            ],
        ])->getParam('infosSC');

        if (is_array($infosSC) && isset($infosSC['apiParamSC'])) {
            return $infosSC['apiParamSC'];
        }

        return [];
    }

    /**
     * @throws GuzzleException
     */
    public function getEventDateAvailability(string $sessionToken, string $eventId): array
    {
        $eventDatesResponse = $this->get('/DispoListeSC', [
            'query' => [
                'instPA' => $sessionToken,
                'defSC' => $this->getEventQuery($eventId),
            ],
        ]);

        $eventDates = [];
        /*
         * For the moment listeSC is still a imploded array
         */
        $eventDateIds = explode(',', $eventDatesResponse->getParam('listeSC'));
        $eventCategories = explode(',', $eventDatesResponse->getParam('listeCat'));
        $eventCategoriesCount = count($eventCategories);

        /*
         * Default info is unavailable
         */
        foreach ($eventDateIds as $i => $eventDateId) {
            $eventDates[$eventDateId] = Client::UNAVAILABLE_INFO;
        }
        /*
         * dispoVOR is used for each SC AND Cat
         */
        $dispoVOR = $eventDatesResponse->getParam('dispoVOR');
        if (strlen($dispoVOR) > 0) {
            $eventDateAvailability = str_split($dispoVOR);

            foreach ($eventDateIds as $i => $eventDateId) {
                $eventAvailabilities = [];
                foreach ($eventCategories as $j => $eventCategory) {
                    $index = ($i * $eventCategoriesCount) + $j;
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
     * @throws GuzzleException
     */
    public function getAccount(string $sessionToken, ?string $bix, ?string $email): ?Account
    {
        if (null !== $bix) {
            $response = $this->doGetInfoClient($sessionToken, $bix);
            if ($response->isStatusOk()) {
                return (new Account())->applyResponse($response);
            }

            return null;
        } elseif (null !== $email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response = $this->get('/TestCompte', [
                'query' => [
                    'instPA' => $sessionToken,
                    'email' => $email,
                ],
            ]);
            if ($response->isStatusOk()) {
                /*
                 * Multiple bix for one email is possible
                 */
                $bixGroup = $response->getParam('bix');
                if (is_string($bixGroup)) {
                    $bixGroup = explode(',', $bixGroup);
                    $account = null;
                    foreach ($bixGroup as $bix) {
                        $response = $this->doGetInfoClient($sessionToken, $bix);
                        if ($response->isStatusOk()) {
                            if (null === $account) {
                                $account = new Account();
                            }
                            $account->applyResponse($response);
                        }
                    }

                    return $account;
                }
            }

            return null;
        }
        throw new \InvalidArgumentException('You must provide a bix or a valid email');
    }

    /**
     * @throws GuzzleException
     */
    protected function doGetInfoClient(string $sessionToken, string $bix): AbstractResponse
    {
        return $this->get('/InfoClient', [
            'query' => [
                'instPA' => $sessionToken,
                'bix' => $bix,
            ],
        ]);
    }

    /**
     * Process availabilities with medium calculus.
     */
    protected function getMediumAvailabilities(array $availabilities = []): string
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
     */
    protected function getBestAvailabilities(array $availabilities = []): string
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
     * @return EventDate[]
     *
     * @throws GuzzleException
     * @throws \Exception
     */
    public function getEventDates(string $sessionToken, string $eventId): array
    {
        $eventDates = [];
        $eventDatesArray = $this->getEventDateParams($sessionToken, $eventId);
        $eventDatesAvailability = $this->getEventDateAvailability($sessionToken, $eventId);

        foreach ($eventDatesArray as $eventDateArray) {
            $eventDate = new EventDate($eventDateArray);
            /*
             * Set event-date availability only if we got an availability for a given sessionId.
             */
            if ('' !== $eventDate->getId() && isset($eventDatesAvailability[$eventDate->getId()])) {
                $eventDate->setAvailability($eventDatesAvailability[$eventDate->getId()]);
            }
            $eventDates[] = $eventDate;
        }

        return $eventDates;
    }

    public function getEventId(): ?string
    {
        return $this->eventId;
    }

    public function setEventId(?string $eventId): Client
    {
        $this->eventId = $eventId;

        return $this;
    }
}
