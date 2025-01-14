<?php declare(strict_types=1);


namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class AdminUserCreatedEvent extends Event
{
    public const NAME = 'admin.user.created';

    /**  @var User */
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
     * @return AdminUserCreatedEvent
     */
    public function setCreatedUser(User $createdUser): AdminUserCreatedEvent
    {
        $this->createdUser = $createdUser;
        return $this;
    }
}
