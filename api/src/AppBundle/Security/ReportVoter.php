<?php
namespace AppBundle\Security;

use AppBundle\Entity\Organisation;
use AppBundle\Entity\Report\Report;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class ReportVoter extends Voter
{
    /** @var string */
    const VIEW = 'view';

    /** @var string */
    const EDIT = 'edit';

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
        return in_array($attribute, [self::VIEW, self::EDIT]) && $subject instanceof Report;
    }

    /**
     * @param string $attribute
     * @param Report $report
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $report, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }


        switch ($attribute) {
            case self::VIEW:
            case self::EDIT:
                return $this->canManage($report, $user);

            default:
                throw new \LogicException('This code should not be reached!');
        }
    }

    /**
     * @param Report $report
     * @param User $user
     * @return bool
     */
    private function canManage(Report $report, User $user)
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($report->getCourtOrder()->getDeputies()->contains($user)) {
            return true;
        }

        $organisation = $report->getCourtOrder()->getOrganisation();
        if ($organisation instanceof Organisation && $organisation->isActivated() && $organisation->containsUser($user)) {
            return true;
        }

        return false;
    }
}
