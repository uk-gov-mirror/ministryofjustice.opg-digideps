<?php

namespace AppBundle\Controller;

use AppBundle\Service\Stats\StatsQueryParameters;
use AppBundle\Service\Stats\QueryFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class StatsController extends RestController
{
    /**
     * @var QueryFactory
     */
    private $QueryFactory;

    public function __construct(QueryFactory $QueryFactory)
    {
        $this->QueryFactory = $QueryFactory;
    }

    /**
     * @Route("/stats")
     * @Method({"GET"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function getMetric(Request $request)
    {
        $params = new StatsQueryParameters($request->query->all());
        $query = $this->QueryFactory->create($params);

        return $query->execute($params);
    }
}
