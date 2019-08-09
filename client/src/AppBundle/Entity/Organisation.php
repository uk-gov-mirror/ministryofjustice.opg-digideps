<?php

namespace AppBundle\Entity;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Entity\User;

/**
 * Organisation
 */
class Organisation
{
    /**
     * @var int
     *
     * @JMS\Type("integer")
     */
    private $id;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @Assert\NotBlank(message="organisation.name.notBlank")
     * @Assert\Length(max=256, maxMessage="organisation.name.maxLength")
     */
    private $name;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    private $emailIdentifier;

    /**
     * @var bool
     *
     * @JMS\Type("boolean")
     * @Assert\NotNull(message="organisation.isActivated.notBlank")
     */
    private $isActivated;

    /**
     * @var User[]
     *
     * @JMS\Type("array<User>")
     */
    private $users = [];

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmailIdentifier()
    {
        return $this->emailIdentifier;
    }

    public function getEmailIdentifierDisplay()
    {
        if ($this->getIsDomainIdentifier()) {
            return '*@' . $this->emailIdentifier;
        } else {
            return $this->emailIdentifier;
        }
    }

    /**
     * @param string $emailIdentifier
     *
     * @return $this
     */
    public function setEmailIdentifier($emailIdentifier)
    {
        $this->emailIdentifier = $emailIdentifier;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsDomainIdentifier()
    {
        return strpos($this->emailIdentifier, '@') === false;
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->getIsDomainIdentifier() ? '' : $this->emailIdentifier;
    }

    /**
     * @param string $emailIdentifier
     *
     * @return $this
     */
    public function setEmailAddress($emailIdentifier)
    {
        $this->emailIdentifier = $emailIdentifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmailDomain()
    {
        return $this->getIsDomainIdentifier() ? $this->emailIdentifier : '';
    }

    /**
     * @param string $emailIdentifier
     *
     * @return $this
     */
    public function setEmailDomain($emailIdentifier)
    {
        $this->emailIdentifier = $emailIdentifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getIsActivated()
    {
        return $this->isActivated;
    }

    /**
     * @param string $isActivated
     *
     * @return $this
     */
    public function setIsActivated($isActivated)
    {
        $this->isActivated = $isActivated;
        return $this;
    }

    /**
     * @return User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function setUsers($users)
    {
        $this->users = $users;

        return $this;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function addUser($user)
    {
        $this->users[] = $user;

        return $this;
    }
}
