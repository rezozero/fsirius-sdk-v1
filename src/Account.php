<?php

declare(strict_types=1);

namespace RZ\FSirius;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class Account implements UserInterface
{
    public const BASE_ROLE = 'ROLE_FORUMSIRIUS_USER';
    public const PRO_ROLE = 'ROLE_FORUMSIRIUS_PRO_USER';

    private ?string $title = null;
    private ?string $lastName = null;
    private ?string $firstName = null;
    private ?string $quality = null;
    private ?string $company = null;
    private ?string $address = null;
    private ?string $zipCode = null;
    private ?string $city = null;
    private ?string $country = null;
    private ?string $phone = null;
    private ?string $altPhone = null;
    private ?string $email = null;
    private ?string $survey = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): Account
    {
        $this->title = $title;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): Account
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): Account
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getQuality(): ?string
    {
        return $this->quality;
    }

    public function setQuality(?string $quality): Account
    {
        $this->quality = $quality;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): Account
    {
        $this->company = $company;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): Account
    {
        $this->address = $address;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): Account
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): Account
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): Account
    {
        $this->country = $country;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): Account
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAltPhone(): ?string
    {
        return $this->altPhone;
    }

    public function setAltPhone(?string $altPhone): Account
    {
        $this->altPhone = $altPhone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): Account
    {
        $this->email = $email;

        return $this;
    }

    public function getSurvey(): ?string
    {
        return $this->survey;
    }

    public function setSurvey(?string $survey): Account
    {
        $this->survey = $survey;

        return $this;
    }

    /**
     * @return $this
     *
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function applyResponse(AbstractResponse $response): Account
    {
        if (!$response->isStatusOk()) {
            throw new \InvalidArgumentException('Source response is not valid.');
        }
        if (null !== $response->getParam('titre') && !empty($response->getParam('titre'))) {
            $this->setTitle($response->getParam('titre'));
        }
        if (null !== $response->getParam('nom') && !empty($response->getParam('nom'))) {
            $this->setLastName($response->getParam('nom'));
        }
        if (null !== $response->getParam('prenom') && !empty($response->getParam('prenom'))) {
            $this->setFirstName($response->getParam('prenom'));
        }
        if (null !== $response->getParam('fonction') && !empty($response->getParam('fonction'))) {
            $this->setQuality($response->getParam('fonction'));
        }
        if (null !== $response->getParam('raisonsociale') && !empty($response->getParam('raisonsociale'))) {
            $this->setCompany($response->getParam('raisonsociale'));
        }
        if (null !== $response->getParam('adresse') && !empty($response->getParam('adresse'))) {
            $this->setAddress($response->getParam('adresse'));
        }
        if (null !== $response->getParam('cp') && !empty($response->getParam('cp'))) {
            $this->setZipCode($response->getParam('cp'));
        }
        if (null !== $response->getParam('ville') && !empty($response->getParam('ville'))) {
            $this->setCity($response->getParam('ville'));
        }
        if (null !== $response->getParam('pays') && !empty($response->getParam('pays'))) {
            $this->setCountry($response->getParam('pays'));
        }
        if (null !== $response->getParam('tel1') && !empty($response->getParam('tel1'))) {
            $this->setPhone($response->getParam('tel1'));
        }
        if (null !== $response->getParam('tel2') && !empty($response->getParam('tel2'))) {
            $this->setAltPhone($response->getParam('tel2'));
        }
        if (null !== $response->getParam('email') && !empty($response->getParam('email'))) {
            $this->setEmail($response->getParam('email'));
        }
        if (null !== $response->getParam('sondage') && !empty($response->getParam('sondage'))) {
            $this->setSurvey($response->getParam('sondage'));
        }

        return $this;
    }

    public function getRoles(): array
    {
        return [
            self::BASE_ROLE,
        ];
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        if (null === $this->getEmail()) {
            throw new \RuntimeException('Account username cannot null');
        }

        return $this->getEmail();
    }

    public function eraseCredentials(): void
    {
        // do nothing, there are no credentials in Sirius account
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }
}
