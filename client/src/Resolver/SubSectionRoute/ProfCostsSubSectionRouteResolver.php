<?php

namespace App\Resolver\SubSectionRoute;

use App\Entity\Report\Report;
use App\Entity\Report\Status;

class ProfCostsSubSectionRouteResolver
{
    const SUMMARY_ROUTE = 'prof_deputy_costs_summary';
    const PREVIOUS_RECEIVED_EXISTS_ROUTE = 'prof_deputy_costs_previous_received_exists';
    const COSTS_RECEIVED_ROUTE = 'prof_deputy_costs_received';
    const SCCO_AMOUNT_ROUTE = 'prof_deputy_costs_amount_scco';
    const INTERIM_EXISTS_ROUTE = 'prof_deputy_costs_inline_interim_19b_exists';
    const INTERIM_ROUTE = 'prof_deputy_costs_inline_interim_19b';
    const BREAKDOWN_ROUTE = 'prof_deputy_costs_breakdown';

    /**
     * @param Report $report
     * @param $state
     * @return string
     */
    public function resolve(Report $report, $state)
    {
        if ($this->sectionNotStarted($state)) {
            return;
        }

        if ($this->sectionIsComplete($state)) {
            return self::SUMMARY_ROUTE;
        }

        if ($this->previousRecievedExistsSubsectionIsIncomplete($report)) {
            return self::PREVIOUS_RECEIVED_EXISTS_ROUTE;
        }

        if ($this->routeIsFixedCosts($report)) {
            return $this->determineCurrentFixedCostSection($report);
        }
        if (!$this->routeIsFixedCosts($report)) {
            return $this->determineCurrentNonFixedCostSection($report);
        }

        return self::SUMMARY_ROUTE;
    }

    /**
     * @param $state
     * @return bool
     */
    private function sectionNotStarted($state)
    {
        return Status::STATE_NOT_STARTED === $state || (Status::STATE_INCOMPLETE !== $state && Status::STATE_DONE !== $state);
    }

    /**
     * @param $state
     * @return bool
     */
    private function sectionIsComplete($state)
    {
        return Status::STATE_DONE === $state;
    }

    /**
     * @param Report $report
     * @param $state
     * @return bool
     */
    private function previousRecievedExistsSubsectionIsIncomplete(Report $report)
    {
        return !$report->getProfDeputyCostsHasPrevious();
    }

    /**
     * @param Report $report
     * @return bool
     */
    private function routeIsFixedCosts(Report $report)
    {
        return $report->hasProfDeputyCostsHowChargedFixedOnly();
    }

    /**
     * @param Report $report
     * @return string
     */
    private function determineCurrentFixedCostSection(Report $report)
    {
        if ($this->fixedCostsSubsectionIsIncomplete($report)) {
            return self::COSTS_RECEIVED_ROUTE;
        }

        if ($this->breakdownCostsIsIncomplete($report)) {
            return self::BREAKDOWN_ROUTE;
        }
    }

    /**
     * @param Report $report
     * @return string
     */
    private function determineCurrentNonFixedCostSection(Report $report)
    {
        if ($this->interimExistsSubsectionIsIncomplete($report)) {
            return self::INTERIM_EXISTS_ROUTE;
        }

        if ($this->interimExists($report) && $this->interimSubsectionIsIncomplete($report)) {
            return self::INTERIM_ROUTE;
        }

        if (!$this->interimExists($report) && $this->fixedCostsSubsectionIsIncomplete($report)) {
            return self::COSTS_RECEIVED_ROUTE;
        }

        if ($this->amountSccoSubsectionIsIncomplete($report)) {
            return self::SCCO_AMOUNT_ROUTE;
        }

        if ($this->breakdownCostsIsIncomplete($report)) {
            return self::BREAKDOWN_ROUTE;
        }
    }

    /**
     * @param Report $report
     * @return bool
     */
    private function fixedCostsSubsectionIsIncomplete(Report $report)
    {
        return !$report->getProfDeputyFixedCost();
    }

    /**
     * @param Report $report
     * @return bool
     */
    private function amountSccoSubsectionIsIncomplete(Report $report)
    {
        return !$report->getProfDeputyCostsAmountToScco();
    }

    /**
     * @param Report $report
     * @return bool
     */
    private function interimExistsSubsectionIsIncomplete(Report $report)
    {
        return !$report->getProfDeputyCostsHasInterim();
    }

    /**
     * @param Report $report
     * @return bool
     */
    private function interimExists(Report $report)
    {
        return 'yes' == strtolower($report->getProfDeputyCostsHasInterim());
    }

    /**
     * @param Report $report
     * @return bool
     */
    private function interimSubsectionIsIncomplete(Report $report)
    {
        return empty($report->getProfDeputyInterimCosts());
    }

    /**
     * @param Report $report
     * @return bool
     */
    private function breakdownCostsIsIncomplete(Report $report)
    {
        return !$report->hasProfDeputyOtherCosts();
    }
}
