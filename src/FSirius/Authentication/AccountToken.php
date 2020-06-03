<?php
declare(strict_types=1);

namespace RZ\FSirius\Authentication;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

class AccountToken extends AbstractToken
{
    /**
     * @var string
     */
    protected $providerKey;

    /**
     * AccountToken constructor.
     *
     * @param UserInterface $user
     * @param string        $providerKey
     * @param array         $roles
     */
    public function __construct($user, string $providerKey, array $roles = [])
    {
        parent::__construct($roles);

        $this->setUser($user);
        $this->setAuthenticated(\count($roles) > 0);
        $this->providerKey = $providerKey;
    }

    /**
     * @inheritDoc
     */
    public function getCredentials()
    {
        return '';
    }

    /**
     * Returns the provider key.
     *
     * @return string The provider key
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function __serialize(): array
    {
        return [$this->providerKey, parent::__serialize()];
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize(array $data): void
    {
        [$this->providerKey, $parentData] = $data;
        $parentData = \is_array($parentData) ? $parentData : unserialize($parentData);
        parent::__unserialize($parentData);
    }
}
