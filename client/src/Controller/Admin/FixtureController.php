<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Report\Report;
use App\Entity\User;
use App\Form\Admin\Fixture\CasrecFixtureType;
use App\Form\Admin\Fixture\CourtOrderFixtureType;
use App\Service\Client\Internal\ReportApi;
use App\Service\Client\Internal\UserApi;
use App\Service\Client\RestClient;
use App\Service\DeputyProvider;
use App\TestHelpers\ClientHelpers;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Service\Client\TokenStorage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;

/**
 * @Route("/admin/fixture")
 */
class FixtureController extends AbstractController
{
    private Environment $twig;
    private SerializerInterface $serializer;
    private RestClient $restClient;
    private ReportApi $reportApi;
    private UserApi $userApi;
    private TokenStorageInterface $tokenStorage;
    private DeputyProvider $deputyProvider;
    private string $symfonyEnvironment;

    public function __construct(
        Environment $twig,
        SerializerInterface $serializer,
        RestClient $restClient,
        ReportApi $reportApi,
        UserApi $userApi,
        TokenStorageInterface $tokenStorage,
        DeputyProvider $deputyProvider,
        string $symfonyEnvironment
    ) {
        $this->twig = $twig;
        $this->serializer = $serializer;
        $this->restClient = $restClient;
        $this->reportApi = $reportApi;
        $this->userApi = $userApi;
        $this->tokenStorage = $tokenStorage;
        $this->deputyProvider = $deputyProvider;
        $this->symfonyEnvironment = $symfonyEnvironment;
    }

    /**
     * @Route("/", name="admin_fixtures")
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     * @Template("@App/Admin/Fixtures/index.html.twig")
     */
    public function fixtures()
    {
        return [];
    }

    /**
     * @Route("/court-orders", name="admin_fixtures_court_orders")
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     * @Template("@App/Admin/Fixtures/courtOrders.html.twig")
     */
    public function courtOrdersAction(Request $request)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(CourtOrderFixtureType::class, null, [
            'deputyType' => $request->get('deputy-type', User::TYPE_LAY),
            'reportType' => $request->get('report-type', Report::TYPE_HEALTH_WELFARE),
            'reportStatus' => $request->get('report-status', Report::STATUS_NOT_STARTED),
            'coDeputyEnabled' => $request->get('co-deputy-enabled', false),
            'activated' => $request->get('activated', true),
            'orgSizeClients' => $request->get('orgSizeClients', 1),
            'orgSizeUsers' => $request->get('orgSizeUsers', 1)
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();
            $courtDate = $request->get('court-date') ? new \DateTime($request->get('court-date')) : new \DateTime('2017-02-01');
            $deputyEmail = $request->query->get('deputy-email', sprintf('original-%s-deputy-%s@fixture.com', strtolower($submitted['deputyType']), mt_rand(1000, 9999)));
            $caseNumber = $request->get('case-number', ClientHelpers::createValidCaseNumber());

            $response = $this->restClient->post('v2/fixture/court-order', json_encode([
                'deputyType' => $submitted['deputyType'],
                'deputyEmail' => $deputyEmail,
                'caseNumber' =>  $caseNumber,
                'reportType' => $submitted['reportType'],
                'reportStatus' => $submitted['reportStatus'],
                'courtDate' => $courtDate->format('Y-m-d'),
                'coDeputyEnabled' => $submitted['coDeputyEnabled'],
                'activated' => $submitted['activated'],
                'orgSizeClients' => $submitted['orgSizeClients'],
                'orgSizeUsers' => $submitted['orgSizeUsers'],
            ]));

            $query = ['query' => ['filter_by_ids' => implode(",", $response['deputyIds'])]];
            $deputiesData = $this->restClient->get('/user/get-all', 'array', [], $query);
            $sanitizedDeputyData = $this->removeNullValues($deputiesData);

            $deputies = $this->serializer->deserialize(json_encode($sanitizedDeputyData), 'App\Entity\User[]', 'json');

            $this->addFlash('fixture', $this->createUsersFlashMessage(array_reverse($deputies), $caseNumber));
        }

        return ['form' => $form->createView()];
    }

    /**
     * @TODO replace with https://symfony.com/doc/4.4/components/serializer.html#skipping-null-values
     * when using Symfony 4+
     */
    private function removeNullValues(array $deputiesDataArray)
    {
        foreach ($deputiesDataArray as $index => $properties) {
            foreach ($properties as $key => $property) {
                if (is_null($property)) {
                    unset($properties[$key]);
                }
            }

            $deputiesDataArray[$index] = $properties;
        }

        return $deputiesDataArray;
    }

    /**
     * @param array $deputies
     * @param string $caseNumber
     * @return string
     */
    public function createUsersFlashMessage(array $deputies, string $caseNumber)
    {
        return $this->twig->render(
            '@App/FlashMessages/fixture-user-created.html.twig',
            ['deputies' => $deputies, 'caseNumber' => $caseNumber]
        );
    }

    /**
     * @Route("/complete-sections/{reportType}/{reportId}", requirements={"id":"\d+"}, methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN') or has_role('ROLE_AD')")
     * @param Request $request
     * @param string $reportType
     * @param $reportId
     * @param KernelInterface $kernel
     * @return JsonResponse
     */
    public function completeReportSectionsAction(Request $request, string $reportType, $reportId): JsonResponse
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        $sections = $request->get('sections');

        $this
            ->restClient
            ->put("v2/fixture/complete-sections/$reportType/$reportId?sections=$sections", []);

        return new JsonResponse(['Report updated']);
    }

    /**
     * @Route("/createAdmin", methods={"GET"})
     * @Security("is_granted('ROLE_SUPER_ADMIN') or has_role('ROLE_ADMIN') or has_role('ROLE_AD')")
     */
    public function createAdmin(Request $request)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        $this
            ->restClient
            ->post("v2/fixture/createAdmin", json_encode([
                "adminType" => $request->query->get('adminType'),
                "email" => $request->query->get('email'),
                "firstName" => $request->query->get('firstName'),
                "lastName" => $request->query->get('lastName'),
                "activated" => $request->query->get('activated')
            ]));

        return new Response();
    }

    /**
     * @Route("/getUserIDByEmail/{email}", methods={"GET"})
     * @Security("is_granted('ROLE_SUPER_ADMIN') or has_role('ROLE_ADMIN') or has_role('ROLE_AD')")
     */
    public function getUserIDByEmail(string $email)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        /** @var array $response */
        $response = json_decode($this
            ->restClient
            ->get("v2/fixture/getUserIDByEmail/$email", 'response')->getBody(), true);

        if ($response['success']) {
            return new Response($response['data']['id']);
        } else {
            return new Response($response['message'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @Route("/createUser", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN', 'ROLE_AD')")
     */
    public function createUser(Request $request)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        $this
            ->restClient
            ->post("v2/fixture/createUser", json_encode([
                "ndr" => $request->query->get('ndr'),
                "deputyType" => $request->query->get('deputyType'),
                "deputyEmail" => $request->query->get('email'),
                "firstName" => $request->query->get('firstName'),
                "lastName" => $request->query->get('lastName'),
                "postCode" => $request->query->get('postCode'),
                "activated" => $request->query->get('activated')
            ]));

        return new Response();
    }

    /**
     * @Route("/deleteUser", methods={"GET"})
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     */
    public function deleteUser(Request $request)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        $this
            ->restClient
            ->post("v2/fixture/deleteUser", json_encode([
                "email" => $request->query->get('email'),
            ]));

        return new Response();
    }

    /**
     * @Route("/createClientAttachDeputy", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN', 'ROLE_AD')")
     */
    public function createClientAndAttachToDeputy(Request $request)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        try {
            $this
                ->restClient
                ->post(
                    "v2/fixture/createClientAttachDeputy",
                    json_encode(
                        [
                            "firstName" => $request->query->get('firstName'),
                            "lastName" => $request->query->get('lastName'),
                            "phone" => $request->query->get('phone'),
                            "address" => $request->query->get('address'),
                            "address2" => $request->query->get('address2'),
                            "county" => $request->query->get('county'),
                            "postCode" => $request->query->get('postCode'),
                            "caseNumber" => $request->query->get('caseNumber'),
                            "deputyEmail" => $request->query->get('deputyEmail')
                        ]
                    )
                );
        } catch (\Throwable $e) {
            throw $e;
        }

        return new Response();
    }

    /**
     * @Route("/createClientAttachOrgs", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN', 'ROLE_AD')")
     */
    public function createClientAndAttachToOrg(Request $request)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        try {
            $this
                ->restClient
                ->post(
                    "v2/fixture/createClientAttachOrgs",
                    json_encode(
                        [
                            "firstName" => $request->query->get('firstName'),
                            "lastName" => $request->query->get('lastName'),
                            "phone" => $request->query->get('phone'),
                            "address" => $request->query->get('address'),
                            "address2" => $request->query->get('address2'),
                            "county" => $request->query->get('county'),
                            "postCode" => $request->query->get('postCode'),
                            "caseNumber" => $request->query->get('caseNumber'),
                            "orgEmailIdentifier" => $request->query->get('orgEmailIdentifier'),
                            "namedDeputyEmail" => $request->query->get('namedDeputyEmail')
                        ]
                    )
                );
        } catch (\Throwable $e) {
            throw $e;
        }

        return new Response();
    }

    /**
     * @Route("/user-registration-token", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN', 'ROLE_AD')")
     */
    public function getUserRegistrationToken(Request $request)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        $email = $request->query->get('email');
        $user = $this->userApi->getByEmail($email);

        return new Response($user->getRegistrationToken());
    }

    /**
     * @Route("/create-casrec", name="casrec_fixture", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     * @Template("@App/Admin/Fixtures/casRec.html.twig")
     *
     * @param Request $request
     * @param KernelInterface $kernel
     *
     * @return array
     */
    public function createCasrec(Request $request)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(CasrecFixtureType::class, null, [
            'deputyType' => $request->get('deputy-type', User::TYPE_LAY),
            'reportType' => $request->get('report-type', 'OPG102'),
            'createCoDeputy' => $request->get('create-co-deputy', false),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();

            $response = $this->restClient->post('v2/fixture/createCasrec', json_encode([
                'deputyType' => $submitted['deputyType'],
                'reportType' => $submitted['reportType'],
                'createCoDeputy' => $submitted['createCoDeputy'],
            ]), [], 'array');

            $this->addFlash('fixture', $this->createCasRecFlashMessage($response));
        }

        return ['form' => $form->createView()];
    }

    /**
     * @param array $deputies
     * @param string $caseNumber
     * @return string
     */
    public function createCasRecFlashMessage(array $data)
    {
        return $this->twig->render('@App/FlashMessages/fixture-casrec-created.html.twig', $data);
    }

    /**
     * @Route("/unsubmit-report/{reportId}", name="unsubmit_report_fixture", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     *
     * @param int $reportId
     * @return void
     * @throws \Exception
     */
    public function unsubmitReport(int $reportId)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        try {
            $report = $this->reportApi->getReport($reportId);
            $this->reportApi->unsubmit($report, $this->getUser(), 'Fixture tests');
        } catch (\Throwable $e) {
            throw new \Exception(sprintf('Could not unsubmit report %s: %s', $reportId, $e->getMessage()));
        }
    }

    /**
     * @Route("/move-users-clients-to-users-org/{userEmail}", name="move_users_clients_to_org", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param string $userEmail
     * @return Response
     */
    public function moveUsersClientsToUsersOrg(string $userEmail)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        try {
            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $this
                ->restClient
                ->get("v2/fixture/move-users-clients-to-users-org/$userEmail", 'response');

            if ($response->getStatusCode() > 399) {
                return new Response(sprintf('Could not move %s clients to users org: %s', $userEmail, $response->getBody()->getContents()), 500);
            }

            return new Response('Clients added to Users org');
        } catch (\Throwable $e) {
            return new Response(sprintf('Could not move %s clients to users org: %s', $userEmail, $e->getMessage()), 500);
        }
    }

    /**
     * @Route("/activateOrg/{orgName}", name="activate_org", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param string $orgName
     * @return Response
     */
    public function activateOrg(string $orgName)
    {
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        try {
            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $this
                ->restClient
                ->get("v2/fixture/activateOrg/$orgName", 'response');

            if ($response->getStatusCode() > 399) {
                return new Response(sprintf('Could not activate %s org: %s', $orgName, $response->getBody()->getContents()), 500);
            }

            return new Response('Org activated');
        } catch (\Throwable $e) {
            return new Response(sprintf('Could not activate %s org: %s', $orgName, $response->getBody()->getContents()), 500);
        }
    }

    /**
     * @Route("/duplicate-client/{clientId}", name="admin_fixture_duplicate_client", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param string $clientId
     * @return Response
     */
    public function duplicateClient(string $clientId)
    {
        var_dump($this->symfonyEnvironment);
        if ($this->symfonyEnvironment === 'prod') {
            throw $this->createNotFoundException();
        }

        try {
            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $this
                ->restClient
                ->get("v2/fixture/duplicate-client/$clientId", 'response');

            if ($response->getStatusCode() > 399) {
                return new Response(sprintf('Could not duplicate client with ID %s: %s', $clientId, $response->getBody()->getContents()), 500);
            }

            return new Response('Client duplicated');
        } catch (\Throwable $e) {
            return new Response(sprintf('Could not duplicate client with ID %s: %s', $clientId, $response->getBody()->getContents()), 500);
        }
    }
}
