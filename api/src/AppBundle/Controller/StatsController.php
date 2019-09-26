<?php

namespace AppBundle\Controller;

use AppBundle\Service\Stats\StatsQueryParameters;
use AppBundle\Service\Stats\MetricQueryFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class StatsController extends RestController
{
    /**
     * @var MetricQueryFactory
     */
    private $metricQueryFactory;

    public function __construct(MetricQueryFactory $metricQueryFactory)
    {
        $this->metricQueryFactory = $metricQueryFactory;
    }

    /**
     * @Route("/stats")
     * @Method({"GET"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function getMetric(Request $request)
    {
        $params = new StatsQueryParameters($request->query->all());
        $query = $this->metricQueryFactory->create($params);

        return $query->execute($params);
    }
}
