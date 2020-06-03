<?php
declare(strict_types=1);

namespace RZ\FSirius\Authentication;

use RZ\FSirius\Account;
use RZ\FSirius\AccountProvider;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class AccountAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var AccountProvider
     */
    protected $accountProvider;
    /**
     * @var string
     */
    protected $providerKey;
    /**
     * @var bool
     */
    protected $hideUserNotFoundExceptions;

    /**
     * AccountAuthenticationProvider constructor.
     *
     * @param AccountProvider $accountProvider
     * @param string          $providerKey
     * @param bool            $hideUserNotFoundExceptions
     */
    public function __construct(AccountProvider $accountProvider, string $providerKey, bool $hideUserNotFoundExceptions = true)
    {
        $this->accountProvider = $accountProvider;
        $this->providerKey = $providerKey;
        $this->hideUserNotFoundExceptions = $hideUserNotFoundExceptions;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            throw new AuthenticationException('The token is not supported by this authentication provider.');
        }

        try {
            $user = $this->accountProvider->loadUserByUsername($token->getUsername());
        } catch (UsernameNotFoundException $e) {
            if ($this->hideUserNotFoundExceptions) {
                throw new BadCredentialsException('Bad credentials.', 0, $e);
            }
            $e->setUsername($token->getUsername());

            throw $e;
        }

        if (!$user instanceof Account) {
            $exception = new AuthenticationException();
            $exception->setToken($token);
            throw $exception;
        }

        return new AccountToken($user, $this->providerKey, $this->getRoles($user));
    }

    /**
     * Override this method if you want to add custom ROLES after during authentication
     * according to your Sirius context and Account survey field.
     *
     * @param UserInterface $user
     *
     * @return array
     */
    protected function getRoles(UserInterface $user): array
    {
        return $user->getRoles();
    }

    /**
     * @inheritDoc
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof AccountToken && $this->providerKey === $token->getProviderKey();
    }
}
