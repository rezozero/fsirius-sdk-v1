<?php

require(dirname(__FILE__) . "/../vendor/autoload.php");

$api_auth = include dirname(__FILE__) . "/api_auth.php";

ini_set('date.timezone', 'Europe/Paris');

try {
    // Theses credentials are only available in API dev mode.
    $client = new \RZ\FSirius\Client(
        $api_auth['url'],
        $api_auth['clientId'],
        null,
        \RZ\FSirius\JsonResponse::class,
        $api_auth['proxy']
    );
    $client->setEventId($api_auth['eventId']);
    $sessionToken = $client->getSessionToken();

    var_dump($client->getEventDates($sessionToken, $api_auth['eventId']));
    var_dump($client->getEventDateAvailability($sessionToken, $api_auth['eventId']));
} catch (\GuzzleHttp\Exception\ClientException $exception) {
    echo $exception->getMessage() . PHP_EOL;
    echo $exception->getResponse()->getBody(). PHP_EOL;
    exit(1);
}
