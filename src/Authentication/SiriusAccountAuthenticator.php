<?php

declare(strict_types=1);

namespace RZ\FSirius\Authentication;

use RZ\FSirius\AccountProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * Base class for creating Symfony Authenticators (login or headless) with Sirius Account API.
 * You must provide your own CredentialsInterface implementation and logic
 * as Sirius does not provide any SSO mechanism.
 */
abstract class SiriusAccountAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly AccountProvider $accountProvider,
        private readonly CredentialsInterface $credentials,
        private readonly string $usernamePath = 'username',
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get($this->usernamePath);
        if (!\is_string($username)) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string.', $this->usernamePath));
        }
        if (\strlen($username) > Security::MAX_USERNAME_LENGTH) {
            throw new BadCredentialsException('Invalid username.');
        }

        return new Passport(
            new UserBadge($username, function ($userIdentifier) {
                return $this->accountProvider->loadUserByIdentifier($userIdentifier);
            }),
            $this->credentials,
            [
                new CsrfTokenBadge('authenticate', $request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }
}
