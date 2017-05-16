<?php

namespace AppBundle\Service;

use AppBundle\Entity\Odr\Odr;
use AppBundle\Entity\Odr\ndrRepository;
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
     * FixDataService constructor.
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
            if ($entity->getType() == Report::TYPE_103) {
                $shortMoneyCatsAdded = $this->reportRepo->addMoneyShortCategoriesIfMissing($entity);
                if ($shortMoneyCatsAdded) {
                    $this->messages[] = "Report {$entity->getId()}: $shortMoneyCatsAdded money short cats added";
                }
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
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }


}
