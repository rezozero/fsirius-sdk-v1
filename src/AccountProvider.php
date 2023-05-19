<?php

declare(strict_types=1);

namespace RZ\FSirius;

use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AccountProvider implements UserProviderInterface
{
    private Client $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     * @param string $identifier
     * @return Account
     */
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
        } catch (GuzzleException $e) {
            throw new UserNotFoundException('', 0, $e);
        }
    }

    public function loadUserByUsername(string $username): Account
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * @inheritDoc
     */
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
        } catch (GuzzleException $e) {
            throw new UserNotFoundException('', 0, $e);
        }
    }

    /**
     * @inheritDoc
     * @param class-string $class
     */
    public function supportsClass(string $class): bool
    {
        return $class === Account::class;
    }
}
