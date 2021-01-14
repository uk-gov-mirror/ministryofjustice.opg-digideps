<?php

namespace App\Entity\Repository;

use App\Entity\Client;
use App\Entity\User;
use App\Service\Search\ClientSearchFilter;
use Doctrine\ORM\EntityRepository;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;

/**
 * ClientRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ClientRepository extends EntityRepository
{
    /** @var ClientSearchFilter */
    private $filter;

    /**
     * Search Clients
     *
     * @param string $query     Search query
     * @param string $orderBy   field to order by
     * @param string $sortOrder order of field order ASC|DESC
     * @param int    $limit     number of results to return
     * @param int $offset
     *
     * @return Client[]|array
     */
    public function searchClients($query = '', $orderBy = 'lastname', $sortOrder = 'ASC', $limit = 100, $offset = 0)
    {
        /** @var SoftDeleteableFilter $filter */
        $filter = $this->_em->getFilters()->getFilter('softdeleteable');
        $filter->disableForEntity(Client::class);

        $alias = 'c';
        $qb = $this->createQueryBuilder($alias);

        if ($query) {
            $this->filter->handleSearchTermFilter($query, $qb, $alias);
        }

        $limit = ($limit <= 100) ? $limit : 100;
        $qb->setMaxResults($limit);
        $qb->setFirstResult((int)$offset);
        $qb->orderBy($alias . '.' . $orderBy, $sortOrder);

        $this->_em->getFilters()->enable('softdeleteable');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findAllClientIdsByUser(User $user)
    {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->executeQuery(
            'select deputy_case.client_id FROM deputy_case WHERE deputy_case.user_id = ?',
            [$user->getId()]
        );

        return array_map('current', $stmt->fetchAll());
    }

    /**
     * @param User $user
     * @param int $clientId
     * @throws \Doctrine\DBAL\DBALException
     */
    public function saveUserToClient(User $user, $clientId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $conn->executeQuery(
            'INSERT INTO deputy_case (client_id, user_id) VALUES (?, ?) ON CONFLICT DO NOTHING',
            [$clientId, $user->getId()]
        );
    }

    /**
     * @param User $user
     * @param int $teamId
     * @throws \Doctrine\DBAL\DBALException
     */
    public function saveUserToTeam(User $user, $teamId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $conn->executeQuery(
            'INSERT INTO user_team (user_id, team_id) VALUES (?, ?) ON CONFLICT DO NOTHING',
            [$user->getId(), $teamId]
        );
    }

    /**
     * @param int $id
     * @return null
     */
    public function getArrayById($id)
    {
        /** @var SoftDeleteableFilter $filter */
        $filter = $this->_em->getFilters()->getFilter('softdeleteable');
        $filter->disableForEntity(Client::class);

        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT c, r, ndr, o, nd, u FROM App\Entity\Client c LEFT JOIN c.reports r LEFT JOIN c.ndr ndr LEFT JOIN c.namedDeputy nd LEFT JOIN c.organisation o LEFT JOIN c.users u WHERE c.id = ?1')
            ->setParameter(1, $id);

        $result = $query->getArrayResult();
        $this->_em->getFilters()->enable('softdeleteable');

        return count($result) === 0 ? null : $result[0];
    }

    /**
     * @param $caseNumber
     * @return array<mixed>|null
     */
    public function getArrayByCaseNumber($caseNumber)
    {
        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Client c WHERE c.caseNumber = ?1')
            ->setParameter(1, $caseNumber);

        $result = $query->getArrayResult();

        return count($result) === 0 ? null : $result[0];
    }

    /**
     * @param ClientSearchFilter $filter
     */
    public function setSearchFilter(ClientSearchFilter $filter): void
    {
        $this->filter = $filter;
    }
}