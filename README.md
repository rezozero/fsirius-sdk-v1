# Forum Sirius APIv1 PHP SDK
**Use Guzzle 7**

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

## Authentication

This package provides a simple `Account` and [Symfony Authenticator](https://symfony.com/doc/current/security/custom_authenticator.html#security-passports): `SiriusAccountAuthenticator` to authenticate Sirius customer. 
However, you must provide your own `CredentialsInterface` implementation because Sirius does not provide SSO mechanism.
For example, you can create a *password-less* authentication system using JWT sent by email combined with `$account->getSurvey()` to
check user permissions.

```php
$surveyFields = explode(';', $user->getSurvey() ?? '');
if (false === $surveyFields || !in_array($this->professionalField, $surveyFields)) {
    throw new BadCredentialsException('account_is_not_professional');
}
```

## Timezone

Be careful, Forum Sirius API servers are using `Europe/Paris` timezone. 
Make sure your application is configured with the same timezone.
