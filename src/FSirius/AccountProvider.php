<?php
declare(strict_types=1);

namespace RZ\FSirius;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AccountProvider implements UserProviderInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername($username)
    {
        $sessionToken = $this->client->getSessionToken();
        if (null !== $sessionToken) {
            $account = $this->client->getAccount($sessionToken, null, $username);
            if (null !== $account) {
                return $account;
            }
        }
        throw new UsernameNotFoundException();
    }

    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user)
    {
        $sessionToken = $this->client->getSessionToken();
        if ($user instanceof Account && null !== $sessionToken) {
            $account = $this->client->getAccount($sessionToken, null, $user->getUsername());
            if (null !== $account) {
                return $account;
            }
            throw new UsernameNotFoundException();
        }
        throw new UnsupportedUserException();
    }

    /**
     * @inheritDoc
     */
    public function supportsClass($class)
    {
        return $class === Account::class;
    }
}
