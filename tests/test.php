<?php

declare(strict_types=1);

use RZ\FSirius\Client;
use RZ\FSirius\JsonResponse;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

require __DIR__.'/../vendor/autoload.php';

$api_auth = include __DIR__.'/api_auth.php';

ini_set('date.timezone', 'Europe/Paris');

try {
    Symfony\Component\ErrorHandler\Debug::enable();
    if (!isset($argv[1])) {
        throw new InvalidArgumentException('You must specify an event ID');
    }

    $eventId = $argv[1];
    if (!\is_string($eventId)) {
        throw new InvalidArgumentException('Event ID is not valid.');
    }

    // These credentials are only available in API dev mode.
    $client = new Client(
        HttpClient::create(),
        $api_auth['url'],
        $api_auth['clientId'],
        JsonResponse::class,
        $api_auth['proxy']
    );
    $client->setEventId($eventId);
    $sessionToken = $client->getSessionToken();

    if (!\is_string($sessionToken)) {
        throw new InvalidArgumentException('Session token is not valid.');
    }

    dump($client->getEventDates($sessionToken, $eventId));
    dump($client->getEventDateAvailability($sessionToken, $eventId));
} catch (HttpExceptionInterface $exception) {
    echo $exception->getMessage().PHP_EOL;
    echo $exception->getResponse()->getContent(false).PHP_EOL;
    exit(1);
} catch (ExceptionInterface $exception) {
    echo $exception->getMessage().PHP_EOL;
    exit(1);
}
