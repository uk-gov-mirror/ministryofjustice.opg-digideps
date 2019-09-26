<?php

namespace AppBundle\Service\Stats\Metrics;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use AppBundle\Service\Stats\StatsQueryParameters;

abstract class MetricQuery
{
    /** @var EntityManager */
    private $em;

    abstract protected function getAggregation(): string;
    abstract protected function getSupportedDimensions(): array;
    abstract protected function getSubquery(): string;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param StatsQueryParameters $sq
     * @return array
     * @throws \Exception
     */
    public function execute(StatsQueryParameters $sq)
    {
        if (is_array($sq->getDimensions())) {
            $this->checkDimensions($sq->getDimensions());
        }

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('amount', 'amount');

        if (is_array($sq->getDimensions())) {
            foreach ($sq->getDimensions() as $dimension) {
                $rsm->addScalarResult($dimension, $dimension);
            }
        }

        $sql = $this->constructQuery($sq->getDimensions());
        $query = $this->em->createNativeQuery($sql, $rsm);

        $startDate = (clone $sq->getStartDate())->setTime(0, 0, 0);
        $endDate = (clone $sq->getEndDate())->setTime(23, 59, 59);

        $query->setParameter('startDate', $startDate->format('Y-m-d H:i:s'));
        $query->setParameter('endDate', $endDate->format('Y-m-d H:i:s'));

        return $query->getResult();
    }

    /**
     * Check all requested are supported by the requested metric
     * @param array $dimensions
     * @throws \Exception
     */
    protected function checkDimensions(array $dimensions)
    {
        foreach ($dimensions as $index => $dimensionName) {
            if (!in_array($dimensionName, $this->getSupportedDimensions())) {
                throw new \Exception("Metric does not support \"$dimensionName\" dimension");
            }
        }
    }

    /**
     * Build an SQL query
     * @param mixed $dimensions The dimensions to group results by
     * @return string
     */
    protected function constructQuery($dimensions)
    {
        $columns = [
            $this->getAggregation() . ' amount'
        ];

        if (is_array($dimensions)) {
            foreach ($dimensions as $dimension) {
                $columns[] = "t.{$dimension} \"{$dimension}\"";
            }
        }

        $select = implode(', ', $columns);
        $sql = "SELECT $select FROM ({$this->getSubquery()}) t WHERE t.date >= :startDate AND t.date <= :endDate";

        if (is_array($dimensions)) {
            $sql .= " GROUP BY " . implode(', ', $dimensions);
        }

        return $sql;
    }
}
