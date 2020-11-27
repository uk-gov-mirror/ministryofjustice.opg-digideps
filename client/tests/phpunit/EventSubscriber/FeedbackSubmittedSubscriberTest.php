<?php declare(strict_types=1);


namespace Tests\AppBundle\EventListener;

use AppBundle\Event\GeneralFeedbackSubmittedEvent;
use AppBundle\Event\PostSubmissionFeedbackSubmittedEvent;
use AppBundle\EventSubscriber\FeedbackSubmittedSubscriber;
use AppBundle\Model\FeedbackReport;
use AppBundle\Service\Mailer\Mailer;
use AppBundle\TestHelpers\UserHelpers;
use PHPUnit\Framework\TestCase;

class FeedbackSubmittedSubscriberTest extends TestCase
{
    /** @test */
    public function getSubscribedEvents()
    {
        self::assertEquals(
            [
                GeneralFeedbackSubmittedEvent::NAME => 'sendGeneralFeedbackEmail',
                PostSubmissionFeedbackSubmittedEvent::NAME => 'sendPostSubmissionFeedbackEmail'
            ],
            FeedbackSubmittedSubscriber::getSubscribedEvents()
        );
    }

    /** @test */
    public function sendGeneralFeedbackEmail()
    {
        $feedbackFormResponse = ['some response' => 'some answer'];
        $event = (new GeneralFeedbackSubmittedEvent())->setFeedbackFormResponse($feedbackFormResponse);

        $mailer = self::prophesize(Mailer::class);
        $mailer->sendGeneralFeedbackEmail($feedbackFormResponse)->shouldBeCalled();

        $sut = new FeedbackSubmittedSubscriber($mailer->reveal());
        $sut->sendGeneralFeedbackEmail($event);
    }

    /** @test */
    public function sendPostSubmissionFeedbackEmail()
    {
        $feedbackReportObject = (new FeedbackReport())
            ->setComments('Some comments')
            ->setSatisfactionLevel(5);

        $submittedByUser = UserHelpers::createUser();

        $event = new PostSubmissionFeedbackSubmittedEvent($feedbackReportObject, $submittedByUser);

        $mailer = self::prophesize(Mailer::class);
        $mailer->sendPostSubmissionFeedbackEmail($feedbackReportObject, $submittedByUser)->shouldBeCalled();

        $sut = new FeedbackSubmittedSubscriber($mailer->reveal());
        $sut->sendPostSubmissionFeedbackEmail($event);
    }
}
