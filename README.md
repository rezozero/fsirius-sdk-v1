# Forum Sirius APIv1 PHP SDK
**Use Guzzle 6**

Available methods:

- Contexte
- ListeSC
- ParamSC
- DispoListeSC

Events « séances » are mapped to `RZ\FSirius\EventDate` object by calling `$client->getEventDates($sessionToken, $eventId)`.

Get a session token by calling: 

```php
$client->setEventId($eventId); 
$sessionToken = $client->getSessionToken();
```

## Timezone

Be careful, Forum Sirius API servers are using `Europe/Paris` timecode. Make sure your application is configured with the same timecode.
