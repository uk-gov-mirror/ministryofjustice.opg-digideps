<?php declare(strict_types=1);

namespace AppBundle\Service\Mailer;

use AppBundle\Entity\Client;
use AppBundle\Entity\Report\Report;
use AppBundle\Entity\User;
use AppBundle\Model\FeedbackReport;
use AppBundle\Service\IntlService;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Bundle\TwigBundle\TwigEngine;

class MailFactoryTest extends TestCase
{
    /** @var ObjectProphecy&Translator */
    private $translator;

    /** @var ObjectProphecy&Router */
    private $router;

    /** @var ObjectProphecy&TwigEngine */
    private $templating;

    /** @var ObjectProphecy&IntlService */
    private $intlService;

    /** @var User */
    private $layDeputy;

    /** @var array */
    private $appBaseURLs;

    /** @var array */
    private $emailSendParams;

    /** @var Client */
    private $client;

    /** @var Report */
    private $submittedReport;

    /** @var Report */
    private $newReport;

    public function setUp(): void
    {
        $this->layDeputy = $this->generateUser();

        $this->client = (new Client())
            ->setFirstname('Joanne')
            ->setLastname('Bloggs')
            ->setCaseNumber('12345678');

        $this->submittedReport = (new Report())
            ->setClient($this->client);

        $this->newReport = new Report();

        $this->appBaseURLs = [
            'front' => 'https://front.base.url',
            'admin' => 'https://admin.base.url'
        ];

        $this->emailSendParams = [
            'from_email' => 'digideps+from@digital.justice.gov.uk',
            'report_submit_to_address' => 'digideps+noop@digital.justice.gov.uk',
            'feedback_send_to_address' => 'digideps+noop@digital.justice.gov.uk',
            'update_send_to_address' => 'updateAddress@digital.justice.gov.uk'
        ];

        $this->translator = self::prophesize('Symfony\Bundle\FrameworkBundle\Translation\Translator');
        $this->router = self::prophesize('Symfony\Bundle\FrameworkBundle\Routing\Router');
        $this->templating = self::prophesize('Symfony\Bundle\TwigBundle\TwigEngine');
        $this->intlService = self::prophesize('AppBundle\Service\IntlService');
    }

    /**
     * @test
     */
    public function createActivationEmail()
    {
        $this->router->generate('homepage', [])->shouldBeCalled()->willReturn('/homepage');
        $this->router->generate('user_activate', [
            'action' => 'activate',
            'token'  => 'regToken'
        ])->shouldBeCalled()->willReturn('/user-activate/regToken');

        $this->translator->trans('activation.fromName', [], 'email')->shouldBeCalled()->willReturn('OPG');
        $this->translator->trans('activation.subject', [], 'email')->shouldBeCalled()->willReturn('Activation Subject');

        $expectedViewParams = [
            'name'             => 'Joe Bloggs',
            'domain'           => 'https://front.base.url/homepage',
            'link'             => 'https://front.base.url/user-activate/regToken',
            'tokenExpireHours' => 48,
            'homepageUrl'      => 'https://front.base.url/homepage',
            'recipientRole'    => 'default'
        ];

        $this->templating->render('AppBundle:Email:user-activate.html.twig', $expectedViewParams)->shouldBeCalled()->willReturn('<html>Rendered body</html>');
        $this->templating->render('AppBundle:Email:user-activate.text.twig', $expectedViewParams)->shouldBeCalled()->willReturn('Rendered body');

        $email = ($this->generateSUT())->createActivationEmail($this->layDeputy);

        self::assertEquals('digideps+from@digital.justice.gov.uk', $email->getFromEmail());
        self::assertEquals('OPG', $email->getFromName());
        self::assertEquals('user@digital.justice.gov.uk', $email->getToEmail());
        self::assertEquals('Joe Bloggs', $email->getToName());
        self::assertEquals('Activation Subject', $email->getSubject());
        self::assertStringContainsString('<html>Rendered body</html>', $email->getBodyHtml());
        self::assertStringContainsString('Rendered body', $email->getBodyText());
    }

    /**
     * @test
     */
    public function createOrgReportSubmissionConfirmationEmail()
    {
        $this->router->generate('homepage', [])->shouldBeCalled()->willReturn('/homepage');

        $this->translator->trans('reportSubmissionConfirmation.fromName', [], 'email')->shouldBeCalled()->willReturn('OPG');
        $this->translator->trans('reportSubmissionConfirmation.subject', ['%clientFullname%' => 'Joanne Bloggs'], 'email')->shouldBeCalled()->willReturn('Submission Confirmation Subject');

        $expectedViewParams = [
            'submittedReport' => $this->submittedReport,
            'newReport'       => $this->newReport,
            'fullDeputyName'  => 'Joe Bloggs',
            'fullClientName'  => 'Joanne Bloggs',
            'caseNumber'      => '12345678',
            'homepageUrl'     => 'https://front.base.url/homepage',
            'recipientRole'   => 'default'
        ];

        $this->templating->render('AppBundle:Email:report-submission-confirm.html.twig', $expectedViewParams)->shouldBeCalled()->willReturn('<html>Rendered body</html>');
        $this->templating->render('AppBundle:Email:report-submission-confirm.text.twig', $expectedViewParams)->shouldBeCalled()->willReturn('Rendered body');

        $email = ($this->generateSUT())->createOrgReportSubmissionConfirmationEmail($this->layDeputy, $this->submittedReport, $this->newReport);

        self::assertEquals('digideps+from@digital.justice.gov.uk', $email->getFromEmail());
        self::assertEquals('OPG', $email->getFromName());
        self::assertEquals('user@digital.justice.gov.uk', $email->getToEmail());
        self::assertEquals('Joe', $email->getToName());
        self::assertEquals('Submission Confirmation Subject', $email->getSubject());
        self::assertStringContainsString('<html>Rendered body</html>', $email->getBodyHtml());
        self::assertStringContainsString('Rendered body', $email->getBodyText());
    }

    /**
     * @todo rename once we drop Notify from the end of the function
     * @test
     */
    public function createResetPasswordEmailNotify()
    {
        $this->router->generate('user_activate', [
            'action' => 'password-reset',
            'token'  => 'regToken'
        ])->shouldBeCalled()->willReturn('/reset-password/regToken');

        $this->translator->trans('resetPassword.fromName', [], 'email')->shouldBeCalled()->willReturn('OPG');
        $this->translator->trans('resetPassword.subject', [], 'email')->shouldBeCalled()->willReturn('Reset Password Subject');

        $email = ($this->generateSUT())->createResetPasswordEmailNotify($this->layDeputy);

        self::assertEquals(MailFactory::NOTIFY_FROM_EMAIL_ID, $email->getFromEmailNotifyID());
        self::assertEquals('OPG', $email->getFromName());
        self::assertEquals('user@digital.justice.gov.uk', $email->getToEmail());
        self::assertEquals('Joe Bloggs', $email->getToName());
        self::assertEquals('Reset Password Subject', $email->getSubject());
        self::assertEquals(MailFactory::RESET_PASSWORD_TEMPLATE_ID, $email->getTemplate());

        $expectedTemplateParams = ['resetLink' => 'https://front.base.url/reset-password/regToken'];
        self::assertEquals($expectedTemplateParams, $email->getParameters());
    }

    /**
     * @test
     */
    public function createGeneralFeedbackEmail()
    {
        $this->translator->trans('feedbackForm.fromName', [], 'email')->shouldBeCalled()->willReturn('OPG');
        $this->translator->trans('feedbackForm.toName', [], 'email')->shouldBeCalled()->willReturn('To Name');
        $this->translator->trans('feedbackForm.subject', [], 'email')->shouldBeCalled()->willReturn('A subject');

        $response = [
                'specificPage' => 'A specific page',
                'page' => 'A page',
                'comments' => 'It was great!',
                'name' => 'Joe Bloggs',
                'email' => 'joe.bloggs@xyz.com',
                'phone' => '07535999222',
                'satisfactionLevel' => '4',
        ];

        $email = ($this->generateSUT())->createGeneralFeedbackEmail($response);

        $this->assertStaticEmailProperties($email);

        self::assertEquals('digideps+noop@digital.justice.gov.uk', $email->getToEmail());
        self::assertEquals(MailFactory::GENERAL_FEEDBACK_TEMPLATE_ID, $email->getTemplate());

        $expectedTemplateParams = [
            'comments' => 'It was great!',
            'satisfactionLevel' => '4',
            'name' => 'Joe Bloggs',
            'phone' => '07535999222',
            'page' => 'A page',
            'email' => 'joe.bloggs@xyz.com',
            'subject' => 'A subject'
        ];

        self::assertEquals($expectedTemplateParams, $email->getParameters());
    }

    /**
     * @test
     */
    public function createPostSubmissionFeedbackEmail()
    {
        $this->translator->trans('feedbackForm.fromName', [], 'email')->shouldBeCalled()->willReturn('OPG');
        $this->translator->trans('feedbackForm.toName', [], 'email')->shouldBeCalled()->willReturn('To Name');
        $this->translator->trans('feedbackForm.subject', [], 'email')->shouldBeCalled()->willReturn('A subject');

        $response = (new FeedbackReport())
            ->setComments('Amazing service!')
            ->setSatisfactionLevel('4');

        $email = ($this->generateSUT())->createPostSubmissionFeedbackEmail($response, $this->generateUser());

        $this->assertStaticEmailProperties($email);

        self::assertEquals('digideps+noop@digital.justice.gov.uk', $email->getToEmail());
        self::assertEquals(MailFactory::POST_SUBMISSION_FEEDBACK_TEMPLATE_ID, $email->getTemplate());

        $expectedTemplateParams = [
            'comments' => 'Amazing service!',
            'satisfactionLevel' => '4',
            'name' => 'Joe Bloggs',
            'phone' => '01211234567',
            'email' => 'user@digital.justice.gov.uk',
            'subject' => 'A subject',
            'userRole' => 'Lay Deputy'
        ];

        self::assertEquals($expectedTemplateParams, $email->getParameters());
    }

    /**
     * @test
     */
    public function createUpdateClientDetailsEmail()
    {
        $this->translator->trans('client.fromName', [], 'email')->shouldBeCalled()->willReturn('OPG');
        $this->translator->trans('client.toName', [], 'email')->shouldBeCalled()->willReturn('To Name');
        $this->translator->trans('client.subject', [], 'email')->shouldBeCalled()->willReturn('A subject');

        $this->intlService->getCountryNameByCountryCode('GB')->shouldBeCalled()->willReturn('United Kingdom');

        $client = $this->generateClient();
        $userSubmittingForm = ($this->generateUser())->setClients([$client]);

        $email = ($this->generateSUT())->createUpdateClientDetailsEmail($client, $userSubmittingForm);

        $this->assertStaticEmailProperties($email);

        self::assertEquals('updateAddress@digital.justice.gov.uk', $email->getToEmail());
        self::assertEquals(MailFactory::CLIENT_DETAILS_CHANGE_TEMPLATE_ID, $email->getTemplate());

        $expectedTemplateParams = [
            'caseNumber' => '12345678',
            'fullName' => 'Joanne Bloggs',
            'address' => '10 Fake Road',
            'address2' => 'Pretendville',
            'address3' => 'Notrealingham',
            'postcode' => 'A12 3BC',
            'countryName' => 'United Kingdom',
            'phone' => '01215553333',
        ];

        self::assertEquals($expectedTemplateParams, $email->getParameters());
    }

    /**
     * @test
     */
    public function createUpdateDeputyDetailsEmail()
    {

    }

    private function assertStaticEmailProperties($email)
    {
        self::assertEquals(MailFactory::NOTIFY_FROM_EMAIL_ID, $email->getFromEmailNotifyID());
        self::assertEquals('OPG', $email->getFromName());
        self::assertEquals('To Name', $email->getToName());
    }

    private function generateSUT()
    {
        return new MailFactory(
            $this->translator->reveal(),
            $this->router->reveal(),
            $this->templating->reveal(),
            $this->intlService->reveal(),
            $this->emailSendParams,
            $this->appBaseURLs
        );
    }

    private function generateUser() : User
    {
        return (new User())
            ->setRegistrationToken('regToken')
            ->setEmail('user@digital.justice.gov.uk')
            ->setFirstname('Joe')
            ->setLastname('Bloggs')
            ->setPhoneMain('01211234567')
            ->setRoleName(User::ROLE_LAY_DEPUTY);
    }

    private function generateClient() : Client
    {
        return (new Client())
            ->setFirstname('Joanne')
            ->setLastname('Bloggs')
            ->setCaseNumber('12345678')
            ->setAddress('10 Fake Road')
            ->setAddress2('Pretendville')
            ->setPostcode('A12 3BC')
            ->setCounty('Notrealingham')
            ->setCountry('GB')
            ->setPhone('01215553333');
    }
}
