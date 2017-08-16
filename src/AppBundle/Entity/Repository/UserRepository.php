<?php

namespace AppBundle\Entity\Repository;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * UserRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends AbstractEntityRepository
{
    /**
     * @param User $user
     */
    public function hardDeleteExistingUser(User $user)
    {
        $existingSoftDeletedUser = $this->findUnfilteredOneBy(['email' => $user->getEmail()]);
        if ($existingSoftDeletedUser != null) {
            // delete soft deleted user a second time to hard delete it
            $this->_em->remove($existingSoftDeletedUser);
            $this->_em->flush($existingSoftDeletedUser);
        }
    }
}
