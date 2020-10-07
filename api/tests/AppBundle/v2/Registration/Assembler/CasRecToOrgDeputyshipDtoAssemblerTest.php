<?php declare(strict_types=1);

namespace Tests\AppBundle\v2\Registration\Assembler;

use AppBundle\Service\ReportUtils;
use AppBundle\v2\Registration\Assembler\CasRecToOrgDeputyshipDtoAssembler;
use AppBundle\v2\Registration\Converter\ReportTypeConverter;
use DateTime;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Tests\AppBundle\v2\Registration\TestHelpers\OrgDeputyshipDTOTestHelper;

class CasRecToOrgDeputyshipDtoAssemblerTest extends TestCase
{
    /** @test */
    public function assembleFromArray_data_is_sanitised()
    {
        $casrecArray = OrgDeputyshipDTOTestHelper::generateValidCasRecOrgDeputyshipArray();
        $casrecArray['Forename'] = '   Roisin  ';
        $casrecArray['Surname'] = ' Murphy     ';
        $casrecArray['Case'] = 'ABCD1234';

        $lastReportDate = new DateTime($casrecArray['Last Report Day']);
        $now = new DateTime();

        /** @var ReportTypeConverter|ObjectProphecy $converter */
        $reportUtils = self::prophesize(ReportUtils::class);
        $reportUtils->convertTypeofRepAndCorrefToReportType($casrecArray['Typeofrep'], $casrecArray['Corref'], 'REALM_PROF')
            ->shouldBeCalled()
            ->willReturn('OPG102');
        $reportUtils->parseCsvDate($casrecArray['Last Report Day'], 20)
            ->shouldBeCalled()
            ->willReturn($lastReportDate);
        $reportUtils->parseCsvDate($casrecArray['Client Date of Birth'], 19)
            ->shouldBeCalled()
            ->willReturn($lastReportDate);
        $reportUtils->generateReportStartDateFromEndDate($lastReportDate)
            ->shouldBeCalled()
            ->willReturn($now);
        $reportUtils->padCaseNumber('abcd1234')
            ->shouldBeCalled()
            ->willReturn('00000001');


        $sut = new CasRecToOrgDeputyshipDtoAssembler($reportUtils->reveal());
        $dto = $sut->assembleSingleDtoFromArray($casrecArray);

        self::assertEquals('Roisin', $dto->getClientFirstname());
        self::assertEquals('Murphy', $dto->getClientLastname());
    }
}
