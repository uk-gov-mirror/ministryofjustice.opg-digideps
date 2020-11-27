<?php declare(strict_types=1);


namespace AppBundle\Event;

use AppBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class UserPasswordResetEvent extends Event
{
    public const NAME = 'password.reset';

    /** @var User */
    private $passwordResetUser;

    public function __construct(User $passwordResetUser)
    {
        $this->passwordResetUser = $passwordResetUser;
    }

    /**
     * @return User
     */
    public function getPasswordResetUser(): User
    {
        return $this->passwordResetUser;
    }

    /**
     * @param User $passwordResetUser
     * @return UserPasswordResetEvent
     */
    public function setPasswordResetUser(User $passwordResetUser): UserPasswordResetEvent
    {
        $this->passwordResetUser = $passwordResetUser;
        return $this;
    }
}
