<?php

namespace Tests\App\Entity;

use App\TestHelpers\ReportSubmissionHelper;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * User Entity test
 */
class UserTest extends KernelTestCase
{
    /** @test */
    public function getNumberOfSubmittedReports()
    {
        $kernel = self::bootKernel();
        $em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $submissionHelper = new ReportSubmissionHelper();
        $submittedSubmissions = [];

        foreach (range(1, 2) as $index) {
            $submittedSubmissions[] = $submissionHelper->generateAndPersistSubmittedReportSubmission(
                $em,
                new DateTime()
            );
        }

        // Submit an extra report for first user
        $submissionHelper->submitAndPersistAdditionalSubmissions(
            $em,
            $submittedSubmissions[0]
        );

        // Create a report submission but dont submit it
        $notSubmittedSubmission = $submissionHelper->generateAndPersistReportSubmission($em);

        self::assertEquals(
            2,
            $submittedSubmissions[0]->getReport()->getClient()->getUsers()[0]->getNumberOfSubmittedReports()
        );

        self::assertEquals(
            1,
            $submittedSubmissions[1]->getReport()->getClient()->getUsers()[0]->getNumberOfSubmittedReports()
        );

        self::assertEquals(
            0,
            $notSubmittedSubmission->getReport()->getClient()->getUsers()[0]->getNumberOfSubmittedReports()
        );
    }
}
