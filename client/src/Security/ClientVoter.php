<?php declare(strict_types=1);

namespace App\Security;

use App\Entity\Client;
use App\Entity\Organisation;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class ClientVoter extends Voter
{
    /** @var string */
    const VIEW = 'view';

    /** @var string */
    const EDIT = 'edit';

    /** @var string */
    const DELETE = 'delete';

    /** @var Security  */
    private $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE]) && $subject instanceof Client;
    }

    /**
     * @param string $attribute
     * @param Client $client
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $client, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }


        switch ($attribute) {
            case self::VIEW:
            case self::EDIT:
                return $this->canManage($client, $user);
            case self::DELETE:
                return $this->canDelete($user);

            default:
                throw new \LogicException('This code should not be reached!');
        }
    }

    /**
     * @param Client $client
     * @param User $user
     * @return bool
     */
    private function canManage(Client $client, User $user)
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($user->isLayDeputy()) {
            return in_array($user->getId(), $client->getUserIds());
        }

        if ($client->userBelongsToClientsOrganisation($user)) {
            return true;
        }

        // todo-aie remove post DDPB-3050, when all access should be denied if ! userBelongsToClientsOrganisation
        if (!$this->clientsOrganisationActive($client) && in_array($user->getId(), $client->getUserIds())) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @return bool
     */
    private function canDelete(User $user): bool
    {
        if ($user->isElevatedAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * @param Client $client
     * @return bool
     */
    private function clientsOrganisationActive(Client $client): bool
    {
        $organisation = $client->getOrganisation();
        return $organisation instanceof Organisation && $organisation->isActivated();
    }
}
