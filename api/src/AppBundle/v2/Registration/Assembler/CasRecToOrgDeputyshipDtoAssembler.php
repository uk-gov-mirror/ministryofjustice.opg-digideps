<?php declare(strict_types=1);


namespace AppBundle\v2\Registration\Assembler;

use AppBundle\Entity\User;
use AppBundle\Service\ReportUtils;
use AppBundle\v2\Registration\DTO\OrgDeputyshipDto;
use DateTime;

class CasRecToOrgDeputyshipDtoAssembler
{
    /**
     * @var ReportUtils
     */
    private $reportUtils;

    public function __construct(ReportUtils $reportUtils)
    {
        $this->reportUtils = $reportUtils;
    }

    public function assembleFromArray(array $data)
    {
        $reportType = $this->reportUtils->convertTypeofRepAndCorrefToReportType(
            $data['Typeofrep'],
            $data['Corref'],
            User::$depTypeIdToRealm[$data['Dep Type']]
        );

        $reportEndDate = $this->reportUtils->parseCsvDate($data['Last Report Day'], '20');
        $reportStartDate = $this->reportUtils->generateReportStartDateFromEndDate($reportEndDate);

        return (new OrgDeputyshipDto())
            ->setDeputyEmail($data['Email'])
            ->setDeputyNumber($data['Deputy No'])
            ->setDeputyFirstname($data['Dep Forename'])
            ->setDeputyLastname($data['Dep Surname'])
            ->setDeputyAddress1($data['Dep Adrs1'])
            ->setDeputyPostcode($data['Dep Postcode'])
            ->setCaseNumber($data['Case'])
            ->setClientFirstname($data['Forename'])
            ->setClientLastname($data['Surname'])
            ->setClientAddress1($data['Client Adrs1'])
            ->setClientAddress2($data['Client Adrs2'])
            ->setClientCounty($data['Client Adrs3'])
            ->setClientPostCode($data['Client Postcode'])
            ->setClientDateOfBirth(new DateTime($data['Client Date of Birth']))
            ->setCourtDate(new DateTime($data['Made Date']))
            ->setReportType($reportType)
            ->setReportStartDate($reportStartDate)
            ->setReportEndDate($reportEndDate);

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
}
