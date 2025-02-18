<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class UserRepository extends ServiceEntityRepository
{
    /** @var QueryBuilder */
    private $qb;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param int $id
     * @return null|array
     */
    public function findUserArrayById($id)
    {
        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT u, c, r FROM App\Entity\User u LEFT JOIN u.clients c LEFT JOIN c.reports r WHERE u.id = ?1 ORDER BY c.id')
            ->setParameter(1, $id);

        $result = $query->getArrayResult();

        return count($result) === 0 ? null : $result[0];
    }

    /**
     * @param Request $request
     * @return array|null
     */
    public function findUsersByQueryParameters(Request $request): ?array
    {
        $this->qb = $this->createQueryBuilder('u');

        $this
            ->handleRoleNameFilter($request)
            ->handleAdManagedFilter($request)
            ->handleNdrEnabledFilter($request)
            ->handleSearchTermFilter($request);

        $order_by = $request->get('order_by', 'id');
        $sort_order = strtoupper($request->get('sort_order', 'DESC'));

        $this->qb
            ->setFirstResult($request->get('offset', 0))
            ->setMaxResults($request->get('limit', 50))
            ->orderBy('u.' . $order_by, $sort_order)
            ->groupBy('u.id');

        if ($request->get('filter_by_ids')) {
            $this->qb->where(sprintf('u.id IN (%s)', $request->get('filter_by_ids')));
        }

        return $this->qb->getQuery()->getResult();
    }

    /**
     * @param Request $request
     * @return UserRepository
     */
    private function handleRoleNameFilter(Request $request): UserRepository
    {
        if (! ($roleName = $request->get('role_name'))) {
            return $this;
        }

        $operand = (strpos($roleName, '%')) !== false ? 'LIKE' : '=';

        $this
            ->qb
            ->andWhere(sprintf('u.roleName %s :role', $operand))
            ->setParameter('role', $roleName);

        return $this;
    }

    /**
     * @param Request $request
     * @return UserRepository
     */
    private function handleAdManagedFilter(Request $request): UserRepository
    {
        if ($request->get('ad_managed')) {
            $this->qb->andWhere('u.adManaged = true');
        }

        return $this;
    }

    /**
     * @param Request $request
     * @return UserRepository
     */
    private function handleNdrEnabledFilter(Request $request): UserRepository
    {
        if ($request->get('ndr_enabled')) {
            $this->qb->andWhere('u.ndrEnabled = true');
        }

        return $this;
    }

    /**
     * @param Request $request
     * @return UserRepository
     */
    private function handleSearchTermFilter(Request $request): UserRepository
    {
        if (! ($searchTerm = $request->get('q'))) {
            return $this;
        }

        if (Client::isValidCaseNumber($searchTerm)) {
            $this->qb->leftJoin('u.clients', 'c');
            $this->qb->andWhere('lower(c.caseNumber) = :cn');
            $this->qb->setParameter('cn', strtolower($searchTerm));
        } else {
            $this->qb->leftJoin('u.clients', 'c');

            $searchTerms = explode(' ', $searchTerm);
            $includeClients = (bool) $request->get('include_clients');

            if (count($searchTerms) === 1) {
                $this->addBroadMatchFilter($searchTerm, $includeClients);
            } else {
                $this->addFullNameExactMatchFilter($searchTerms[0], $searchTerms[1], $includeClients);
            }
        }

        return $this;
    }

    /**
     * @param string $searchTerm
     * @param bool $includeClients
     * @return string
     */
    public function addBroadMatchFilter(string $searchTerm, bool $includeClients)
    {
        $nameBasedQuery = '(lower(u.email) LIKE :qLike OR lower(u.firstname) LIKE :qLike OR lower(u.lastname) LIKE :qLike)';

        if ($includeClients) {
            $nameBasedQuery .= ' OR (lower(c.firstname) LIKE :qLike OR lower(c.lastname) LIKE :qLike)';
        }

        $this->qb->setParameter('qLike', '%' . strtolower($searchTerm) . '%');
        $this->qb->andWhere($nameBasedQuery);
    }

    /**
     * @param string $firstName
     * @param string $lastname
     * @param bool $includeClients
     * @return string
     */
    public function addFullNameExactMatchFilter(string $firstName, string $lastname, bool $includeClients)
    {
        $nameBasedQuery = '(lower(u.firstname) = :firstname AND lower(u.lastname) = :lastname)';

        if ($includeClients) {
            $nameBasedQuery .= ' OR (lower(c.firstname) = :firstname AND lower(c.lastname) = :lastname)';
        }

        $this->qb->setParameter('firstname', strtolower($firstName));
        $this->qb->setParameter('lastname', strtolower($lastname));

        $this->qb->andWhere($nameBasedQuery);
    }

    /**
     * @return User[]
     */
    public function findInactive($select = null)
    {
        $thirtyDaysAgo = new DateTime();
        $thirtyDaysAgo->sub(new DateInterval('P30D'));

        $reportSubquery = $this->_em->createQueryBuilder()
            ->select('1')
            ->from('App\Entity\Report\Report', 'r')
            ->andWhere('r.client = c');

        $ndrSubquery = $this->_em->createQueryBuilder()
            ->select('1')
            ->from('App\Entity\Ndr\Ndr', 'n')
            ->andWhere('n.client = c');

        $qb = $this->createQueryBuilder('u');
        $qb
            ->select($select)
            ->leftJoin('u.clients', 'c')
            ->andWhere('u.registrationDate < :reg_cutoff')
            ->andWhere('u.roleName = :lay_deputy_role')
            ->andWhere($qb->expr()->not($qb->expr()->exists($reportSubquery->getDQL())))
            ->andWhere($qb->expr()->not($qb->expr()->exists($ndrSubquery->getDQL())))
            ->setParameter('reg_cutoff', $thirtyDaysAgo)
            ->setParameter('lay_deputy_role', User::ROLE_LAY_DEPUTY);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function findActiveLaysInLastYear()
    {
        $oneYearAgo = (new DateTime())->modify('-1 Year')->format('Y-m-d');

        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
SELECT u.id,
u.firstname as user_first_name,
u.lastname as user_last_name,
u.email as user_email,
u.phone_main as user_phone_number,
u.registration_date,
u.last_logged_in,
c.firstname as client_first_name,
c.lastname as client_last_name,
COUNT(r.id) as submitted_reports
FROM dd_user as u
LEFT JOIN deputy_case as dc on u.id = dc.user_id
LEFT JOIN client as c on dc.client_id = c.id
LEFT JOIN report as r on c.id = r.client_id
WHERE r.submit_date is not null AND u.role_name = 'ROLE_LAY_DEPUTY' AND u.last_logged_in >= :oneYearAgo
GROUP BY u.id, u.firstname, u.lastname, u.email, u.registration_date, u.last_logged_in, c.firstname, c.lastname
SQL;

        $stmt = $conn->prepare($sql);
        $stmt->execute(['oneYearAgo' => $oneYearAgo]);

        return $stmt->fetchAllAssociative();
    }
}
