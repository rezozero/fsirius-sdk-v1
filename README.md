# Forum Sirius APIv1 PHP SDK

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
