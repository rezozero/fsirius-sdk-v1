<?php
declare(strict_types=1);

namespace RZ\FSirius;

use Symfony\Component\Security\Core\User\UserInterface;

final class Account implements UserInterface
{
    const BASE_ROLE = 'ROLE_FORUMSIRIUS_USER';
    const PRO_ROLE = 'ROLE_FORUMSIRIUS_PRO_USER';

    /**
     * @var string|null
     */
    private $title;
    /**
     * @var string|null
     */
    private $lastName;
    /**
     * @var string|null
     */
    private $firstName;
    /**
     * @var string|null
     */
    private $quality;
    /**
     * @var string|null
     */
    private $company;
    /**
     * @var string|null
     */
    private $address;
    /**
     * @var string|null
     */
    private $zipCode;
    /**
     * @var string|null
     */
    private $city;
    /**
     * @var string|null
     */
    private $country;
    /**
     * @var string|null
     */
    private $phone;
    /**
     * @var string|null
     */
    private $altPhone;
    /**
     * @var string|null
     */
    private $email;
    /**
     * @var string|null
     */
    private $survey;

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     *
     * @return Account
     */
    public function setTitle(?string $title): Account
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     *
     * @return Account
     */
    public function setLastName(?string $lastName): Account
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     *
     * @return Account
     */
    public function setFirstName(?string $firstName): Account
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getQuality(): ?string
    {
        return $this->quality;
    }

    /**
     * @param string|null $quality
     *
     * @return Account
     */
    public function setQuality(?string $quality): Account
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @param string|null $company
     *
     * @return Account
     */
    public function setCompany(?string $company): Account
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @param string|null $address
     *
     * @return Account
     */
    public function setAddress(?string $address): Account
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * @param string|null $zipCode
     *
     * @return Account
     */
    public function setZipCode(?string $zipCode): Account
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     *
     * @return Account
     */
    public function setCity(?string $city): Account
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     *
     * @return Account
     */
    public function setCountry(?string $country): Account
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     *
     * @return Account
     */
    public function setPhone(?string $phone): Account
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAltPhone(): ?string
    {
        return $this->altPhone;
    }

    /**
     * @param string|null $altPhone
     *
     * @return Account
     */
    public function setAltPhone(?string $altPhone): Account
    {
        $this->altPhone = $altPhone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     *
     * @return Account
     */
    public function setEmail(?string $email): Account
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSurvey(): ?string
    {
        return $this->survey;
    }

    /**
     * @param string|null $survey
     *
     * @return Account
     */
    public function setSurvey(?string $survey): Account
    {
        $this->survey = $survey;

        return $this;
    }

    /**
     * @param AbstractResponse $response
     *
     * @return $this
     */
    public function applyResponse(AbstractResponse $response): self
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

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        return [
            static::BASE_ROLE
        ];
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        if (null === $this->getEmail()) {
            throw new \RuntimeException('Account username cannot null');
        }
        return $this->getEmail();
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function eraseCredentials()
    {
        // do nothing, there are no credentials in Sirius account
    }
}
