<?php

namespace Tests\AppBundle\v2\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Controller\AbstractTestController;

class DeputyControllerTest extends AbstractTestController
{
    /** @var array */
    private $headers = [];

    /** @var null|string */
    private static $tokenAdmin = null;

    /** @var \Symfony\Component\BrowserKit\Response */
    private $response;

    /**
     * {@inheritDoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $layUser = self::fixtures()->createUser(['setEmail' => 'lay-user@dep-controller.test', 'setRoleName' => 'ROLE_LAY_DEPUTY']);
        $layClient = self::fixtures()->createClient($layUser, ['setEmail' => 'lay-client-1@dep-controller.test']);
        self::fixtures()->createReport($layClient);

        $paUser = self::fixtures()->createUser(['setEmail' => 'pa-user@dep-controller.test', 'setRoleName' => 'ROLE_PA_NAMED']);
        $paClient = self::fixtures()->createClient($paUser, ['setEmail' => 'pa-client-1@dep-controller.test']);
        $paOrg = self::fixtures()->createOrganisation('foo', 'foo@foo.com', true);
        $paOrg->addUser($paUser)->addClient($paClient);

        self::fixtures()->flush()->clear();
    }

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        if (null === self::$tokenAdmin) {
            self::$tokenAdmin = $this->loginAsAdmin();
        }

        $this->headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_AuthToken' => self::$tokenAdmin];
    }

    /**
     * @test
     */
    public function getByIdAction_throws_exception_if_deputy_not_found()
    {
        $invalidDeputyId = 9435;
        $this
            ->makeApiRequestForDeputy($invalidDeputyId)
            ->assertRedResponse($invalidDeputyId);
    }

    /**
     * @test
     */
    public function getByIdAction_returns_transformed_response_if_lay_deputy_found()
    {
        /** @var EntityRepository $repo */
        $repo = self::fixtures()->getRepo('User');

        /** @var User $deputy */
        $deputy = $repo->findOneByEmail('lay-user@dep-controller.test');

        $this
            ->makeApiRequestForDeputy($deputy->getId())
            ->assertGreenResponseForLay($deputy);
    }

    /**
     * @test
     */
    public function getByIdAction_returns_transformed_response_if_org_deputy_found()
    {
        /** @var EntityRepository $repo */
        $repo = self::fixtures()->getRepo('User');

        /** @var User $deputy */
        $deputy = $repo->findOneByEmail('pa-user@dep-controller.test');

        $this
            ->makeApiRequestForDeputy($deputy->getId())
            ->assertGreenResponseForOrg($deputy);
    }

    /**
     * @param int $deputyId
     * @return DeputyControllerTest
     */
    private function makeApiRequestForDeputy(int $deputyId): DeputyControllerTest
    {
        self::$frameworkBundleClient->request('GET', "/v2/deputy/$deputyId", [], [], $this->headers);
        $this->response = self::$frameworkBundleClient->getResponse();

        return $this;
    }

    /**
     * @param int $deputyId
     */
    private function assertRedResponse(int $deputyId)
    {
        $responseContent = json_decode($this->response->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->response->getStatusCode());
        $this->assertTrue($this->response->headers->contains('Content-Type', 'application/json'));
        $this->assertFalse($responseContent['success']);
        $this->assertEquals("Deputy id $deputyId not found", $responseContent['message']);
    }

    /**
     * @param User $deputy
     */
    private function assertGreenResponseForLay(User $deputy)
    {
        $responseContent = json_decode($this->response->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $this->response->getStatusCode());
        $this->assertTrue($this->response->headers->contains('Content-Type', 'application/json'));
        $this->assertTrue($responseContent['success']);
        $this->assertEquals($deputy->getEmail(), $responseContent['data']['email']);
        $this->assertEquals('lay-client-1@dep-controller.test', $responseContent['data']['clients'][0]['email']);
        $this->assertEquals(1, $responseContent['data']['clients'][0]['total_report_count']);
        $this->assertcount(0, $responseContent['data']['organisations']);
    }

    /**
     * @param User $deputy
     */
    private function assertGreenResponseForOrg(User $deputy)
    {
        $responseContent = json_decode($this->response->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $this->response->getStatusCode());
        $this->assertTrue($this->response->headers->contains('Content-Type', 'application/json'));
        $this->assertTrue($responseContent['success']);
        $this->assertEquals($deputy->getEmail(), $responseContent['data']['email']);
        $this->assertEquals('foo', $responseContent['data']['organisations'][0]['name']);
        $this->assertCount(0, $responseContent['data']['clients']);
    }
}