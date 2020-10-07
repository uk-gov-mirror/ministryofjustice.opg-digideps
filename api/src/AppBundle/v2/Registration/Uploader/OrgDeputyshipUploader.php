<?php declare(strict_types=1);


namespace AppBundle\v2\Registration\Uploader;

use AppBundle\Entity\Client;
use AppBundle\Entity\NamedDeputy;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Report\Report;
use AppBundle\Factory\OrganisationFactory;
use AppBundle\Service\OrgService;
use AppBundle\v2\Assembler\ClientAssembler;
use AppBundle\v2\Assembler\NamedDeputyAssembler;
use AppBundle\v2\Registration\DTO\OrgDeputyshipDto;
use Doctrine\ORM\EntityManagerInterface;

class OrgDeputyshipUploader
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var OrganisationFactory */
    private $orgFactory;

    /** @var Organisation|null */
    private $currentOrganisation;

    /** @var ClientAssembler */
    private $clientAssembler;

    /** @var NamedDeputyAssembler */
    private $namedDeputyAssembler;

    /** @var array[] */
    private $added;

    /** @var NamedDeputy|null */
    private $namedDeputy;

    /** @var Client|null */
    private $client;

    public function __construct(
        EntityManagerInterface $em,
        OrganisationFactory $orgFactory,
        ClientAssembler $clientAssembler,
        NamedDeputyAssembler $namedDeputyAssembler
    ) {
        $this->em = $em;
        $this->orgFactory = $orgFactory;
        $this->clientAssembler = $clientAssembler;
        $this->namedDeputyAssembler = $namedDeputyAssembler;

        $this->added = ['clients' => [], 'discharged_clients' => [], 'named_deputies' => [], 'reports' => [], 'organisations' => []];
        $this->namedDeputy = null;
        $this->client = null;
    }

    /**
     * @param OrgDeputyshipDto[] $deputyshipDtos
     * @return array
     * @throws \Exception
     */
    public function upload(array $deputyshipDtos)
    {
        $uploadResults = ['errors' => 0];

        foreach ($deputyshipDtos as $deputyshipDto) {
            // WRITE TESTS AROUND ANYTHING THAT COULD BREAK IN A TRY CATCH BLOCK (see if we can add to an errors array in OrgDeputyshipDto as part of ->valid())
            //  - Email not being provided
            //  -
            if (!$deputyshipDto->isValid()) {
                $uploadResults['errors']++;
                continue;
            }

            $this->handleNamedDeputy($deputyshipDto);
            $this->handleOrganisation($deputyshipDto);
            $this->handleClient($deputyshipDto);
            $this->handleReport($deputyshipDto);
        }

        $uploadResults['added'] = $this->added;
        return $uploadResults;
    }

    private function handleNamedDeputy(OrgDeputyshipDto $dto)
    {
        $namedDeputy = ($this->em->getRepository(NamedDeputy::class))->findOneBy(
            [
                'email1' => $dto->getDeputyEmail(),
                'deputyNo' => $dto->getDeputyNumber(),
                'firstname' => $dto->getDeputyFirstname(),
                'lastname' => $dto->getDeputyLastname(),
                'address1' => $dto->getDeputyAddress1(),
                'addressPostcode' => $dto->getDeputyPostCode()
            ]
        );

        if (is_null($namedDeputy)) {
            $namedDeputy = $this->namedDeputyAssembler->assembleFromOrgDeputyshipDto($dto);

            $this->em->persist($namedDeputy);
            $this->em->flush();

            $this->added['named_deputies'][] = $namedDeputy->getId();
        }

        $this->namedDeputy = $namedDeputy;
    }

    private function handleOrganisation(OrgDeputyshipDto $dto)
    {
        $orgDomainIdentifier = explode('@', $dto->getDeputyEmail())[1];
        $this->currentOrganisation = $foundOrganisation = ($this->em->getRepository(Organisation::class))->findOneBy(['emailIdentifier' => $orgDomainIdentifier]);

        if (is_null($foundOrganisation)) {
            $organisation = $this->orgFactory->createFromFullEmail(OrgService::DEFAULT_ORG_NAME, $dto->getDeputyEmail());
            $this->em->persist($organisation);
            $this->em->flush();

            $this->currentOrganisation = $organisation;

            $this->added['organisations'][] = $organisation->getId();
        }
    }

    // Finds existing client
    // Creates non-existent client
    // Assigns named deputy to client
    // Assigns org to client
    // Updates courtdate for existing clients
    // Updates named deputy for existing client if in same org
    // Adds case number for newly created to added array
    private function handleClient(OrgDeputyshipDto $dto): Client
    {
        $client = ($this->em->getRepository(Client::class))->findOneBy(['caseNumber' => $dto->getCaseNumber()]);

        if (is_null($client)) {
            $client = $this->clientAssembler->assembleFromOrgDeputyshipDto($dto);
            $client->setNamedDeputy($this->namedDeputy);

            if (!is_null($this->currentOrganisation)) {
                $this->currentOrganisation->addClient($client);
                $client->setOrganisation($this->currentOrganisation);
            }

            $this->added['clients'][] = $dto->getCaseNumber();
        } else {
            $client->setCourtDate($dto->getCourtDate());

            if ($client->getOrganisation() === $this->currentOrganisation) {
                $client->setNamedDeputy($this->namedDeputy);
            }
        }

        $this->em->persist($client);
        $this->em->flush();

        return $this->client = $client;
    }

    // Finds existing report
    // Creates non-existent report
    // Updates report type for existing report if report has not been submitted or is currently unsubmitted
    // Adds report to client
    // Adds case number and end date for newly created report to added array
    private function handleReport(OrgDeputyshipDto $dto)
    {
        $report = $this->client->getCurrentReport();

        if ($report) {
            if (!$report->getSubmitted() && empty($report->getUnSubmitDate())) {
                // Add audit logging for report type changing
                $report->setType($dto->getReportType());
            }
        } else {
            $report = new Report(
                $this->client,
                $dto->getReportType(),
                $dto->getReportStartDate(),
                $dto->getReportEndDate()
            );

            $this->client->addReport($report);
        }

        $this->em->persist($report);
        $this->em->flush();

        $this->added['reports'][] = $this->client->getCaseNumber() . '-' . $dto->getReportEndDate()->format('Y-m-d');
    }
}
