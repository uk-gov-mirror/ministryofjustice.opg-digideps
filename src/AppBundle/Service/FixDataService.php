<?php

namespace AppBundle\Service;

use AppBundle\Entity\Odr\Odr;
use AppBundle\Entity\Odr\OdrRepository;
use AppBundle\Entity\Report\Report;
use AppBundle\Entity\Repository\ReportRepository;
use Doctrine\ORM\EntityManager;

class FixDataService
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ReportRepository
     */
    private $reportRepo;

    /**
     * @var OdrRepository
     */
    private $ndrRepo;

    /**
     * @var array
     */
    private $messages = [];


    /**
     * Total records processed
     *
     * @var int
     */
    private $totalProcessed = 0;

    /**
     * FixDataService constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->reportRepo = $this->em->getRepository(Report::class);
        $this->ndrRepo = $this->em->getRepository(Odr::class);
    }

    public function fixReports()
    {
        $reports = $this->reportRepo->findAll();

        foreach ($reports as $entity) {
            $debtsAdded = $this->reportRepo->addDebtsToReportIfMissing($entity);
            if ($debtsAdded) {
                $this->messages[] = "Report {$entity->getId()}: added $debtsAdded debts";
            }
            $feesAdded = $this->reportRepo->addFeesToReportIfMissing($entity);
            if ($feesAdded) {
                $this->messages[] = "Report {$entity->getId()}: added $feesAdded fees";
            }
            $shortMoneyCatsAdded = $this->reportRepo->addMoneyShortCategoriesIfMissing($entity);
            if ($shortMoneyCatsAdded) {
                $this->messages[] = "Report {$entity->getId()}: $shortMoneyCatsAdded money short cats added";
            }
        }

        $this->em->flush();

        return $this;
    }

    public function fixNdrs()
    {
        $ndrs = $this->ndrRepo->findAll();

        foreach ($ndrs as $entity) {
            $debtsAdded = $this->ndrRepo->addDebtsToOdrIfMissing($entity);
            if ($debtsAdded) {
                $this->messages[] = "Odr {$entity->getId()}: added $debtsAdded debts";
            }
            $incomeBenefitsAdded = $this->ndrRepo->addIncomeBenefitsToOdrIfMissing($entity);
            if ($incomeBenefitsAdded) {
                $this->messages[] = "Odr {$entity->getId()}: $incomeBenefitsAdded income benefits added";
            }
        }

        $this->em->flush();

        return $this;
    }

    /**
     * Fixes the reporting dates by pushing the start and end dates forward by
     * a period of 56 days.
     */
    public function fixPaReportingPeriods()
    {
        $reports = $this->reportRepo->findAll();
        $this->totalProcessed = 0;
        /** @var Report $report */
        foreach ($reports as $report) {
            try {
                $client = $report->getClient();
                if (!$client) {
                    throw new \RuntimeException('no client found');
                }
                $user = $client->getUsers()->first();
                if (!$user) {
                    throw new \RuntimeException('no user found');
                }

                if ($user->isPaDeputy()) {
                    $this->messages[] = "Report {$report->getId()}: already executed on 14/6/2017";
//                    $oldPeriod = $report->getStartDate()->format('d-M-Y') . '-->' .
//                        $report->getEndDate()->format('d-M-Y');

//                    $report->setStartDate($report->getStartDate()->add(new \DateInterval('P56D')));
//                    $report->setEndDate($report->getEndDate()->add(new \DateInterval('P56D')));

//                    $this->messages[] = "Report {$report->getId()}: Reporting period updated FROM " .
//                        $oldPeriod . ' TO ' .
//                        $report->getStartDate()->format('d-M-Y') . ' --> ' .
//                        $report->getEndDate()->format('d-M-Y');
//                    $this->totalProcessed++;
                } else {
                    $this->messages[] = "Report {$report->getId()}: Skipping... (not a pa client report)";
                }
            } catch (\Exception $e) {
                $this->messages[] = "Report {$report->getId()}: 
                ERROR - could not be processed with start date of " .
                    $report->getStartDate()->format('d-M-Y') . ' to ' .
                    $report->getStartDate()->format('d-M-Y') .
                    'Exception: ' . $e->getMessage();
            }
        }

        $this->em->flush();

        return $this;
    }

    /**
     * Fixes the reporting start date ensuring a period of 365 days, not 366.
     * Ie correct is 1 year in past + 1 day. Ensuring no overlaps
     */
    public function fixPaStartDate()
    {
        $reports = $this->reportRepo->findAll();
        $this->totalProcessed = 0;
        /** @var Report $report */
        foreach ($reports as $report) {
            try {
                $client = $report->getClient();
                if (!$client) {
                    throw new \RuntimeException('no client found');
                }
                $user = $client->getUsers()->first();
                if (!$user) {
                    throw new \RuntimeException('no user found');
                }

                if ($user->isPaDeputy()) {
                    //                    $this->messages[] = "Report {$report->getId()}: already executed on 14/6/2017";
                    $oldPeriod = $report->getStartDate()->format('d-M-Y') . '-->' .
                        $report->getEndDate()->format('d-M-Y');

                    $reportStartDate = PaService::generateReportStartDateFromEndDate($report->getEndDate());
                    $report->setStartDate($reportStartDate);

                    $newPeriod = $report->getStartDate()->format('d-M-Y') . '-->' .
                        $report->getEndDate()->format('d-M-Y');

                    $this->messages[] = "Report {$report->getId()}: Reporting period updated FROM " .
                        $oldPeriod . ' TO ' .
                        $newPeriod;
                    $this->totalProcessed++;
                } else {
                    $this->messages[] = "Report {$report->getId()}: Skipping... (not a pa client report)";
                }
            } catch (\Exception $e) {
                $this->messages[] = "Report {$report->getId()}: 
                ERROR - could not be processed with start date of " .
                    $report->getStartDate()->format('d-M-Y') . ' to ' .
                    $report->getStartDate()->format('d-M-Y') .
                    'Exception: ' . $e->getMessage();
            }
        }

        $this->em->flush();

        return $this;
    }

    public function fixReportSubmittedBy()
    {
        $reports = $this->reportRepo->findAll();
        $this->totalProcessed = 0;
        /** @var Report $report */
        foreach ($reports as $report) {
            $reportId = $report->getId();
            try {
                // fix reports submitted, and without a submittedBy
                if ($report->getSubmitted()) {
                    if (!$report->getSubmittedBy()) {
                        if (!$report->getClient()) {
                            throw new \RuntimeException('no client found');
                        }
                        $users = $report->getClient()->getUsers();
                        $user = $users->first();
                        if (!$user) {
                            throw new \RuntimeException('no user. skipped'); // should never happen, but live data not available for testing atm
                        }

                        $report->setSubmittedBy($user);
                        $this->messages[] = "Report $reportId : set correctly among the " . count($users) . ' user(s)';
                    }
                } else {
                    if ($report->getSubmittedBy()) {
                        $report->setSubmittedBy(null);// fix previous migration on staging that acted on all the reports
                        $this->messages[] = "Report $reportId : not submitted. setSubmittedBy set to null";
                    }
                }
            } catch (\Exception $e) {
                $this->messages[] = "Report $reportId: " . $e->getMessage();
            }
        }

        $this->em->flush();

        return $this;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return int
     */
    public function getTotalProcessed()
    {
        return $this->totalProcessed;
    }
}
