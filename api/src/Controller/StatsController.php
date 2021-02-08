<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Repository\UserRepository;
use App\Service\Stats\StatsQueryParameters;
use App\Service\Stats\QueryFactory;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class StatsController extends RestController
{
    private QueryFactory $QueryFactory;
    private UserRepository $userRepository;

    public function __construct(QueryFactory $QueryFactory, UserRepository $userRepository)
    {
        $this->QueryFactory = $QueryFactory;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/stats", methods={"GET"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function getMetric(Request $request)
    {
        $params = new StatsQueryParameters($request->query->all());
        $query = $this->QueryFactory->create($params);

        return $query->execute($params);
    }
}
