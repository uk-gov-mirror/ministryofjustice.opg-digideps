<?php declare(strict_types=1);


namespace App\TestHelpers;

use App\Entity\Client;
use App\Entity\Report\ReportSubmission;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReportSubmissionHelper extends KernelTestCase
{
    /**
     * @param EntityManager $em
     * @return ReportSubmission
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generateAndPersistReportSubmission(EntityManager $em)
    {
        $client = new Client();
        $report = (new ReportTestHelper())->generateReport($client);
        $client->addReport($report);
        $user = (new UserTestHelper())->createAndPersistUser($em, $client);
        $reportSubmission = new ReportSubmission($report, $user);

        $em->persist($client);
        $em->persist($report);
        $em->persist($user);
        $em->persist($reportSubmission);
        $em->flush();

        return $reportSubmission;
    }

    public function generateAndPersistSubmittedReportSubmission(EntityManager $em, DateTime $submitDate)
    {
        $rs = $this->generateAndPersistReportSubmission($em);
        $report = $rs->getReport()
            ->setSubmitDate($submitDate)
            ->setSubmitted(true);
        $rs->setCreatedOn($submitDate);

        $em->persist($rs);
        $em->persist($report);
        $em->flush();

        return $rs;
    }

    public function submitAndPersistAdditionalSubmissions(EntityManager $em, ReportSubmission $lastSubmission)
    {
        $client = $lastSubmission->getReport()->getClient();

        $report = (new ReportTestHelper())->generateReport(
            $client,
            $lastSubmission->getReport()->getType(),
            $lastSubmission->getReport()->getSubmitDate()->modify('+366 days')
        );

        $client->addReport($report);

        $reportSubmission = (new ReportSubmission($report, $lastSubmission->getReport()->getClient()->getUsers()[0]));

        $report
            ->setSubmitDate(new DateTime('tomorrow'))
            ->setSubmitted(true)
            ->setClient($client);

        $em->persist($client);
        $em->persist($report);
        $em->persist($reportSubmission);

        $em->flush();
    }
}
