<?php

namespace AppBundle\Service\Stats;

class StatsQueryParameters
{
    public $metric;
    public $dimensions;
    public $startDate;
    public $endDate;

    public function __construct($parameters)
    {
        $this->metric = $parameters['metric'];
        $this->dimensions = $parameters['dimension'];
        $this->startDate = $parameters['startDate'];
        $this->endDate = $parameters['endDate'];

        if ($this->metric === null) {
            throw new \InvalidArgumentException('Must specify a metric');
        }

        if (!is_array($this->dimensions) && !is_null($this->dimensions)) {
            throw new \InvalidArgumentException('Invalid dimension');
        }

        if ($this->startDate === null && $this->endDate === null) {
            $this->endDate = new \DateTime();
            $this->startDate = new \DateTime('-30 days');
        } elseif ($this->startDate === null) {
            $this->endDate = new \DateTime($this->endDate);
            $this->startDate = clone $this->endDate;
            $this->startDate->sub(new \DateInterval('P30D'));
        } elseif ($this->endDate === null) {
            $this->startDate = new \DateTime($this->startDate);
            $this->endDate = clone $this->startDate;
            $this->endDate->add(new \DateInterval('P30D'));
        } else {
            $this->startDate = new \DateTime($this->startDate);
            $this->endDate = new \DateTime($this->endDate);
        }
    }
}
