<?php

declare(strict_types=1);

require dirname(__FILE__).'/../vendor/autoload.php';

$api_auth = include dirname(__FILE__).'/api_auth.php';

ini_set('date.timezone', 'Europe/Paris');

try {
    Symfony\Component\ErrorHandler\Debug::enable();
    if (!isset($argv[1])) {
        throw new InvalidArgumentException('You must specify an event ID');
    }

    $eventId = $argv[1];
    // These credentials are only available in API dev mode.
    $client = new RZ\FSirius\Client(
        $api_auth['url'],
        $api_auth['clientId'],
        null,
        RZ\JsonResponse::class,
        $api_auth['proxy']
    );
    $client->setEventId($eventId);
    $sessionToken = $client->getSessionToken();

    dump($client->getEventDates($sessionToken, $eventId));
    dump($client->getEventDateAvailability($sessionToken, $eventId));
} catch (GuzzleHttp\Exception\ClientException $exception) {
    echo $exception->getMessage().PHP_EOL;
    echo $exception->getResponse()->getBody().PHP_EOL;
    exit(1);
}
