<?php declare(strict_types=1);


namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class OrgUserCreatedEvent extends Event
{
    public const NAME = 'org.user.created';

    /** @var User */
    private $createdUser;

    public function __construct(User $createdUser)
    {
        $this->createdUser = $createdUser;
    }

    /**
     * @return User
     */
    public function getCreatedUser(): User
    {
        return $this->createdUser;
    }

    /**
     * @param User $createdUser
     * @return OrgUserCreatedEvent
     */
    public function setCreatedUser(User $createdUser): OrgUserCreatedEvent
    {
        $this->createdUser = $createdUser;
        return $this;
    }
}
