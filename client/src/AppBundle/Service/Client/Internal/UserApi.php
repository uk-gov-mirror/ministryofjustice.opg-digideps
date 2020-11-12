<?php declare(strict_types=1);

namespace AppBundle\Service\Client\Internal;

use AppBundle\Entity\User;
use AppBundle\Event\UserDeletedEvent;
use AppBundle\Event\UserUpdatedEvent;
use AppBundle\Service\Client\RestClientInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserApi
{
    private const USER_ENDPOINT_BY_ID = 'user/%s';

    /**  @var RestClientInterface */
    private $restClient;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        RestClientInterface $restClient,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->restClient = $restClient;
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param int $id
     * @param array $jmsGroups
     * @return User
     */
    public function get(int $id, array $jmsGroups = [])
    {
        return $this->restClient->get(sprintf(self::USER_ENDPOINT_BY_ID, $id), 'User', $jmsGroups);
    }

    /**
     * @param array $jmsGroups
     *
     * @return User
     */
    public function getUserWithData(array $jmsGroups = [])
    {
        $jmsGroups[] = 'user';
        $jmsGroups = array_unique($jmsGroups);
        sort($jmsGroups);

        /** @var User */
        $user = $this->tokenStorage->getToken()->getUser();

        return $this->restClient->get(
            sprintf(self::USER_ENDPOINT_BY_ID, $user->getId()),
            'User',
            $jmsGroups
        );
    }

    /**
     * @param User $preUpdateUser
     * @param User $postUpdateUser
     * @param array $jmsGroups
     * @param string $trigger
     * @return mixed
     */
    public function update(User $preUpdateUser, User $postUpdateUser, string $trigger, $jmsGroups = [])
    {
        $response = $this->restClient->put(
            sprintf(self::USER_ENDPOINT_BY_ID, $preUpdateUser->getId()),
            $postUpdateUser,
            $jmsGroups
        );

        $userUpdatedEvent = new UserUpdatedEvent(
            $preUpdateUser,
            $postUpdateUser,
            $this->tokenStorage->getToken()->getUser(),
            $trigger
        );

        $this->eventDispatcher->dispatch(UserUpdatedEvent::NAME, $userUpdatedEvent);

        return $response;
    }

    public function delete(User $userToDelete, string $trigger)
    {
        $this->restClient->delete(sprintf(self::USER_ENDPOINT_BY_ID, $userToDelete->getId()));

        /** @var User */
        $deletedBy = $this->tokenStorage->getToken()->getUser();

        $userDeletedEvent = new UserDeletedEvent($userToDelete, $deletedBy, $trigger);
        $this->eventDispatcher->dispatch(UserDeletedEvent::NAME, $userDeletedEvent);
    }
}
