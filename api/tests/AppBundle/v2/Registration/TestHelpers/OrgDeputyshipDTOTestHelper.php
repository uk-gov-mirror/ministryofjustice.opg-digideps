<?php declare(strict_types=1);

namespace Tests\AppBundle\v2\Registration\TestHelpers;

use AppBundle\Entity\Client;
use AppBundle\Entity\NamedDeputy;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\Report\Report;
use AppBundle\Entity\Repository\ClientRepository;
use AppBundle\Entity\Repository\NamedDeputyRepository;
use AppBundle\Entity\Repository\OrganisationRepository;
use AppBundle\Entity\Repository\ReportRepository;
use AppBundle\Service\ReportUtils;
use AppBundle\v2\Registration\Assembler\CasRecToOrgDeputyshipDtoAssembler;
use AppBundle\v2\Registration\Converter\ReportTypeConverter;
use AppBundle\v2\Registration\DTO\OrgDeputyshipDto;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Faker\Provider\en_GB\Address;

class OrgDeputyshipDTOTestHelper
{
    public static function generateCasRecOrgDeputyshipJson(int $validCount, int $invalidCount)
    {
        $deputyships = [];

        if ($validCount > 0) {
            foreach (range(1, $validCount) as $index) {
                $deputyships[] = self::generateValidCasRecOrgDeputyshipArray();
            }
        }

        if ($invalidCount > 0) {
            foreach (range(1, $invalidCount) as $index) {
                $deputyships[] = self::generateInvalidOrgDeputyshipArray();
            }
        }

        return json_encode($deputyships);
    }


    /**
     * @param int $validCount
     * @param int $invalidCount
     * @return OrgDeputyshipDto[]
     */
    public static function generateOrgDeputyshipDtos(int $validCount, int $invalidCount)
    {
        $json = self::generateCasRecOrgDeputyshipJson($validCount, $invalidCount);
        $dtos = [];
        $reportUtils = new ReportUtils();
        $assembler = new CasRecToOrgDeputyshipDtoAssembler($reportUtils);

        foreach (json_decode($json, true) as $dtoArray) {
            $dtos[] = $assembler->assembleFromArray($dtoArray);
        }

        return $dtos;
    }

    /**
     * @param int $clients
     * @param int $namedDeputies
     * @param int $reports
     * @param int $errors
     * @return false|string
     * @throws \Exception
     */
    public static function generateOrgDeputyshipResponseJson(int $clients, int $discharged, int $namedDeputies, int $reports, int $errors)
    {
        $result = ['errors' => $errors];
        $added = ['clients' => [], 'discharged_clients' => [], 'named_deputies' => [], 'reports' => []];

        if ($clients > 0) {
            foreach (range(1, $clients) as $index) {
                $added['clients'] = random_int(10000000, 99999999);
            }
        }

        if ($discharged > 0) {
            foreach (range(1, $discharged) as $index) {
                $added['discharged_clients'] = random_int(10000000, 99999999);
            }
        }

        if ($namedDeputies > 0) {
            foreach (range(1, $namedDeputies) as $index) {
                $added['named_deputies'] = random_int(10000000, 99999999);
            }
        }

        if ($reports > 0) {
            foreach (range(1, $reports) as $index) {
                $added['reports'] = random_int(10000000, 99999999);
            }
        }

        $result['added'] = $added;
        return json_encode($result);
    }

    public static function createOrgDeputyshipModel(int $validCount, int $invalidCount)
    {
        $deputyships = [];

        if ($validCount > 0) {
            foreach (range(1, $validCount) as $index) {
                $deputyships[] = (new OrgDeputyshipDto())->setIsValid(true);
            }
        }

        if ($invalidCount > 0) {
            foreach (range(1, $invalidCount) as $index) {
                $deputyships[] = (new OrgDeputyshipDto())->setIsValid(false);
            }
        }

        return $deputyships;
    }


    /**
     * @return array
     */
    public static function generateValidCasRecOrgDeputyshipArray()
    {
        $faker = Factory::create();
        $courtOrderMadeDate = DateTimeImmutable::createFromMutable($faker->dateTimeThisYear);
        $reportDueDate = $courtOrderMadeDate->modify('12 months - 1 day');

        return [
            'Email'        => $faker->companyEmail,
            'Deputy No'    => (string) $faker->randomNumber(8),
            'Dep Postcode' => Address::postcode(),
            'Dep Forename' => $faker->firstName,
            'Dep Surname'  => $faker->lastName,
            // Add 23 back in for PA tests
            'Dep Type'     => (string) $faker->randomElement([21,22,24,25,26,27,29,50,63]),
            'DepAddr No'   => $faker->buildingNumber,
            'Dep Adrs1'    => $faker->streetName,
            'Dep Adrs2'    => Address::cityPrefix() . ' ' . $faker->city,
            'Dep Adrs3'    => $faker->city,
            'Dep Adrs4'    => Address::county(),
            'Dep Adrs5'    => 'UK',
            'Case'       => (string) $faker->randomNumber(8),
            'Forename'   => $faker->firstName,
            'Surname'    => $faker->lastName,
            'Corref'     => 'A3',
            'Report Due' => $reportDueDate->format('d-M-Y'),
            'Forename' => $faker->firstName,
            'Surname'  => $faker->lastName,
            'Client Adrs1' => $faker->buildingNumber . ' ' . $faker->streetName,
            'Client Adrs2' => Address::cityPrefix() . ' ' . $faker->city,
            'Client Adrs3' => Address::county(),
            'Client Adrs4' => null,
            'Client Adrs5' => null,
            'Client Postcode' => Address::postcode(),
            'Client Date of Birth' => $faker->dateTime->format('d-M-Y'),
            'Made Date' => $courtOrderMadeDate->format('d-M-Y'),
            'Typeofrep' => $faker->randomElement(['OPG102', 'OPG103']),
            'Last Report Day' => '19-Jan-2021'
        ];

//        Case
//        Forename
//        Surname
//        Client Adrs1
//        Client Adrs2
//        Client Adrs3
//        Client Adrs4
//        Client Adrs5
//        Client Postcode
//        Client Date of Birth
//        Dep Type
//        Email
//        Email2
//        Email3
//        Deputy No
//        Dep Forename
//        Dep Surname
//        DepAddr No
//        Dep Adrs1
//        Dep Adrs2
//        Dep Adrs3
//        Dep Adrs4
//        Dep Adrs5
//        Dep Postcode
//        Made Date
//        Dig
//        Digdate	Foreign	Corref
//        Last Report Day
//        Typeofrep
//        Team
//        Fee Payer
//        Corres
//        Sett Comp
    }

    private static function generateInvalidOrgDeputyshipArray()
    {
        $invalid = self::generateValidCasRecOrgDeputyshipArray();
        $invalid['Email'] = '';

        return $invalid;
    }

    public static function namedDeputyWasCreated(OrgDeputyshipDto $orgDeputyship, NamedDeputyRepository $namedDeputyRepository)
    {
        return $namedDeputyRepository->findOneBy(['email1' => $orgDeputyship->getDeputyEmail()]) instanceof NamedDeputy;
    }

    public static function organisationWasCreated(string $emailIdentifier, OrganisationRepository $orgRepo)
    {
        return $orgRepo->findOneBy(['emailIdentifier' => $emailIdentifier]) instanceof Organisation;
    }

    public static function clientWasCreated(OrgDeputyshipDto $orgDeputyship, ClientRepository $clientRepo)
    {
        return $clientRepo->findOneBy(['caseNumber' => $orgDeputyship->getCaseNumber()]) instanceof Client;
    }

    public static function clientAndOrgAreAssociated(OrgDeputyshipDto $orgDeputyship, ClientRepository $clientRepo, OrganisationRepository $orgRepo)
    {
        $client = $clientRepo->findOneBy(['caseNumber' => $orgDeputyship->getCaseNumber()]);

        $orgEmailIdentifier = explode('@', $orgDeputyship->getDeputyEmail())[1];
        $org = $orgRepo->findOneBy(['emailIdentifier' => $orgEmailIdentifier]);

        return $org->getClients()->contains($client) && $client->getOrganisation() === $org;
    }

    public static function clientAndNamedDeputyAreAssociated(OrgDeputyshipDto $orgDeputyship, ClientRepository $clientRepo, NamedDeputyRepository $namedDeputyRepo)
    {
        $client = $clientRepo->findOneBy(['caseNumber' => $orgDeputyship->getCaseNumber()]);
        $namedDeputy = $namedDeputyRepo->findOneBy(['email1' => $orgDeputyship->getDeputyEmail()]);

        return $client->getNamedDeputy() === $namedDeputy;
    }

    public static function clientAndNamedDeputyAreNotAssociated(OrgDeputyshipDto $orgDeputyship, ClientRepository $clientRepo, NamedDeputyRepository $namedDeputyRepo)
    {
        $client = $clientRepo->findOneBy(['caseNumber' => $orgDeputyship->getCaseNumber()]);
        $namedDeputy = $namedDeputyRepo->findOneBy(['email1' => $orgDeputyship->getDeputyEmail()]);

        return !($client->getNamedDeputy() === $namedDeputy);
    }

    public static function clientHasAReportOfType(string $caseNumber, string $reportType, ClientRepository $clientRepo)
    {
        $client = $clientRepo->findOneBy(['caseNumber' => $caseNumber]);
        return $client->getReports()->first()->getType() == $reportType;
    }

    public static function reportTypeHasChanged(string $oldReportType, Client $client, ReportRepository $reportRepo)
    {
        $report = $reportRepo->findOneBy(['client' => $client]);
        return $report->getType() !== $oldReportType;
    }

    /**
     * @param OrgDeputyshipDto $dto
     * @param EntityManager $em
     * @return NamedDeputy
     */
    public static function ensureNamedDeputyInUploadExists(OrgDeputyshipDto $dto, EntityManager $em)
    {
        $namedDeputy = (new NamedDeputy())
            ->setEmail1($dto->getDeputyEmail())
            ->setDeputyNo($dto->getDeputyNumber())
            ->setFirstname($dto->getDeputyFirstname())
            ->setLastname($dto->getDeputyLastname())
            ->setAddress1($dto->getDeputyAddress1())
            ->setAddressPostcode($dto->getDeputyPostcode());

        $em->persist($namedDeputy);
        $em->flush();

        return $namedDeputy;
    }

    /**
     * @param string $emailIdentifier
     * @param EntityManager $em
     * @return Organisation
     */
    public static function ensureOrgInUploadExists(string $orgIdentifier, EntityManager $em)
    {
        $organisation = (new Organisation())
            ->setName('Your Organisation')
            ->setEmailIdentifier($orgIdentifier)
            ->setIsActivated(false);

        $em->persist($organisation);
        $em->flush();

        return $organisation;
    }

    public static function ensureClientInUploadExists(OrgDeputyshipDto $dto, EntityManager $em)
    {
        $client = (new Client())
            ->setCaseNumber($dto->getCaseNumber())
            ->setFirstname($dto->getClientFirstname())
            ->setLastname($dto->getClientLastname())
            ->setCourtDate(new DateTime());

        $em->persist($client);
        $em->flush();

        return $client;
    }

    public static function ensureAReportExistsAndIsAssociatedWithClient(
        Client $client,
        EntityManager $em,
        string $reportType = '103-5',
        string $startDate = '01-11-2019',
        string $endDate = '01-11-2019'
    ) {
        $report = new Report($client, $reportType, new DateTime($startDate), new DateTime($endDate));
        $client->addReport($report);

        $em->persist($report);
        $em->persist($client);
        $em->flush();

        return $report;
    }
}
