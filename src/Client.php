<?php

declare(strict_types=1);

namespace RZ\FSirius;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Client
{
    public const SESSION_ID = 'fsirius_sdk_session';
    public const AVAILABLE_SEATS = 'V';
    public const LATEST_SEATS = 'O';
    public const NO_MORE_SEATS = 'R';
    public const FORBIDDEN_EVENT_DATE = '-';
    public const UNAVAILABLE_INFO = '?';

    private ?string $eventId = null;

    private HttpClientInterface $client;

    /**
     * @param class-string<AbstractResponse> $responseType
     */
    public function __construct(
        HttpClientInterface $client,
        string $endpoint,
        private readonly string $clientId,
        private readonly string $responseType = JsonResponse::class,
        ?string $proxy = null,
    ) {
        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Endpoint '.$endpoint.' must be a valid URL');
        }

        $config = [
            'base_uri' => $endpoint,
            'headers' => [
                'Accept' => call_user_func([$this->responseType, 'getContentType']),
                'X-Origin' => 'RZ-FSirius-SDK',
            ],
            'timeout' => 4,
            'max_redirects' => 3,
        ];
        if (null !== $proxy) {
            $config['proxy'] = $proxy;
        }

        $this->client = $client->withOptions($config);
    }

    /**
     * @return class-string<AbstractResponse>
     */
    public function getResponseType(): string
    {
        return $this->responseType;
    }

    /**
     * GET request with no credentials.
     *
     * @throws TransportExceptionInterface
     */
    public function get(string $url, array $options = []): AbstractResponse
    {
        $responseType = $this->getResponseType();

        return new $responseType($this->client->request('GET', $url, $options));
    }

    protected function getEventQuery(string $eventId): string
    {
        return 'spec='.$eventId;
    }

    /**
     * Get valid season items.
     *
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getSessionToken(array $options = []): ?string
    {
        $options['spec'] = $this->getEventId();
        $compiledOptions = http_build_query($options);

        return $this->get('/Contexte', [
            'query' => [
                'inst' => $this->clientId,
                'session' => $this->clientId.'_'.self::SESSION_ID,
                'paramsURL' => $compiledOptions,
            ],
        ])->getSessionToken();
    }

    /**
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
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
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
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
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
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
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
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
     * @throws TransportExceptionInterface
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
                self::FORBIDDEN_EVENT_DATE => 0,
                self::AVAILABLE_SEATS => 1,
                self::LATEST_SEATS => 2,
                self::NO_MORE_SEATS => 3,
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

        return self::NO_MORE_SEATS;
    }

    /**
     * Process availabilities with at least one of the best availability found.
     */
    protected function getBestAvailabilities(array $availabilities = []): string
    {
        if (count($availabilities) > 0) {
            if (in_array(self::AVAILABLE_SEATS, $availabilities)) {
                return self::AVAILABLE_SEATS;
            }
            if (in_array(self::LATEST_SEATS, $availabilities)) {
                return self::LATEST_SEATS;
            }

            return self::NO_MORE_SEATS;
        }

        return self::UNAVAILABLE_INFO;
    }

    /**
     * @return EventDate[]
     *
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
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
