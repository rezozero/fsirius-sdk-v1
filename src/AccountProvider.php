<?php

declare(strict_types=1);

namespace RZ\FSirius;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AccountProvider implements UserProviderInterface
{
    public function __construct(private readonly Client $client)
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[\Override]
    public function loadUserByIdentifier(string $identifier): Account
    {
        try {
            $sessionToken = $this->client->getSessionToken();
            if (null !== $sessionToken) {
                $account = $this->client->getAccount($sessionToken, null, $identifier);
                if (null !== $account) {
                    return $account;
                }
            }
            throw new UserNotFoundException();
        } catch (HttpExceptionInterface $e) {
            throw new UserNotFoundException('', 0, $e);
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function loadUserByUsername(string $username): Account
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[\Override]
    public function refreshUser(UserInterface $user): Account
    {
        try {
            $sessionToken = $this->client->getSessionToken();
            if ($user instanceof Account && null !== $sessionToken) {
                $account = $this->client->getAccount($sessionToken, null, $user->getUsername());
                if (null !== $account) {
                    return $account;
                }
                throw new UserNotFoundException();
            }
            throw new UnsupportedUserException();
        } catch (HttpExceptionInterface $e) {
            throw new UserNotFoundException('', 0, $e);
        }
    }

    /**
     * @param class-string $class
     */
    #[\Override]
    public function supportsClass(string $class): bool
    {
        return Account::class === $class;
    }
}
