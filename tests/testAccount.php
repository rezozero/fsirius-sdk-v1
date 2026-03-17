<?php

declare(strict_types=1);

use RZ\FSirius\AccountProvider;
use RZ\FSirius\Client;
use RZ\FSirius\JsonResponse;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

require __DIR__.'/../vendor/autoload.php';

$api_auth = include __DIR__.'/api_auth.php';

ini_set('date.timezone', 'Europe/Paris');

try {
    Symfony\Component\ErrorHandler\Debug::enable();
    if (!isset($argv[1])) {
        throw new InvalidArgumentException('You must specify an email');
    }

    $email = $argv[1];
    // These credentials are only available in API dev mode.
    $client = new Client(
        HttpClient::create(),
        $api_auth['url'],
        $api_auth['clientId'],
        JsonResponse::class,
        $api_auth['proxy']
    );
    $accountProvider = new AccountProvider($client);
    dump($accountProvider->loadUserByUsername($argv[1]));
} catch (ExceptionInterface $exception) {
    echo $exception->getMessage().PHP_EOL;
    exit(1);
}
