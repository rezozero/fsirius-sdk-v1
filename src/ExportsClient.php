<?php

declare(strict_types=1);

namespace RZ\FSirius;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Replaces the default ForumSirius\Client with this one to use the exported data endpoints.
 *
 * /_site/{{inst}}/dispo/exportParamSC.json
 * /_site/{{inst}}/dispo/exportDispo.json
 */
final class ExportsClient extends Client
{
    public function __construct(
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly string $exportsEndpoint,
        HttpClientInterface $client,
        string $endpoint,
        string $clientId,
        string $responseType = JsonResponse::class,
        ?string $proxy = null,
    ) {
        parent::__construct($client, $endpoint, $clientId, $responseType, $proxy);
    }

    /**
     * @return string no need for a session token for the exported data endpoints
     */
    public function getSessionToken(array $options = []): string
    {
        return '';
    }

    private function getExportedAvailabilities(): array
    {
        $cacheKey = 'sirius_exported_availabilities';
        $cacheItem = $this->cacheItemPool->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }
        $endpoint = sprintf('%s/%s/dispo/exportDispo.json', $this->exportsEndpoint, $this->clientId);
        $response = $this->client->request('GET', $endpoint);
        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException('Failed to get exported availabilities');
        }
        $data = \json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $cacheItem->set($data);
        $cacheItem->expiresAfter(300);
        $this->cacheItemPool->save($cacheItem);

        return $data;
    }

    private function getExportedCatalog(): array
    {
        $cacheKey = 'sirius_exported_catalog';
        $cacheItem = $this->cacheItemPool->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }
        $endpoint = sprintf('%s/%s/dispo/exportParamSC.json', $this->exportsEndpoint, $this->clientId);
        $response = $this->client->request('GET', $endpoint);
        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException('Failed to get exported catalog');
        }

        $data = \json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $cacheItem->set($data);
        $cacheItem->expiresAfter(600);
        $this->cacheItemPool->save($cacheItem);

        return $data;
    }

    public function getEventDateIds(string $sessionToken, string $eventId): array
    {
        return array_map(
            function (array $eventDate) {
                return $eventDate['sc'];
            },
            $this->getEventDateParams($sessionToken, $eventId)
        );
    }

    public function getEventDateParams(string $sessionToken, string $eventId): array
    {
        $catalog = $this->getExportedCatalog();
        if (!isset($catalog['infosSC']) || !isset($catalog['infosSC']['apiParamSC'])) {
            throw new \RuntimeException('Invalid catalog data');
        }

        return array_values(array_filter($catalog['infosSC']['apiParamSC'], function (array $item) use ($eventId) {
            return $item['spec'] === intval($eventId);
        }));
    }

    public function getEventDateAvailability(string $sessionToken, string $eventId): array
    {
        $eventDatesResponse = $this->getExportedAvailabilities();
        $eventDates = [];

        if (!isset($eventDatesResponse['catego'])) {
            throw new \RuntimeException('Missing "catego" in response');
        }
        if (!isset($eventDatesResponse['seance']) || !is_array($eventDatesResponse['seance'])) {
            throw new \RuntimeException('Missing "seance" array in response');
        }

        $eventDateIds = $this->getEventDateIds($sessionToken, $eventId);

        foreach ($eventDateIds as $eventDateId) {
            $eventDates[$eventDateId] = Client::UNAVAILABLE_INFO;
        }

        foreach ($eventDatesResponse['seance'] as $eventDate) {
            if (!isset($eventDate['id']) || !isset($eventDate['dispo'])) {
                continue;
            }
            $eventDates[$eventDate['id']] = $this->getBestAvailabilities(mb_str_split($eventDate['dispo']));
        }

        return $eventDates;
    }
}
