<?php declare(strict_types=1);


namespace Tests\AppBundle\Service\Formatter;

use AppBundle\EventListener\RestInputOuputFormatter;
use AppBundle\Service\Formatter\RestFormatter;
use AppBundle\Service\Validator\RestArrayValidator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;

class RestFormatterTest extends TestCase
{
    private \Prophecy\Prophecy\ObjectProphecy $inputOutputFormatter;
    private \Prophecy\Prophecy\ObjectProphecy $validator;
    private RestFormatter $sut;

    public function setUp(): void
    {
        $this->inputOutputFormatter = self::prophesize(RestInputOuputFormatter::class);
        $this->validator = self::prophesize(RestArrayValidator::class);

        $this->sut = new RestFormatter(
            $this->inputOutputFormatter->reveal(),
            $this->validator->reveal()
        );
    }

    /** @test */
    public function deserializeBodyContent()
    {
        $incomingRequest = new Request();
        $expectedContentArray = ['aKey' => 'some data'];
        $this->inputOutputFormatter
            ->requestContentToArray($incomingRequest)
            ->shouldBeCalled()
            ->willReturn($expectedContentArray);

        $assertions = ['aDataKey' => 'someAssertion'];

        $this->validator->validateArray($expectedContentArray, $assertions)->shouldBeCalled();

        $actualContentArray = $this->sut->deserializeBodyContent($incomingRequest, $assertions);

        self::assertEquals($expectedContentArray, $actualContentArray);
    }

    /** @test */
    public function setJmsSerialiserGroups()
    {
        $serialiserGroups = ['group1', 'group2'];

        $this->inputOutputFormatter
            ->addContextModifier(Argument::type('Callable'))
            ->shouldBeCalled();

        $this->sut->setJmsSerialiserGroups($serialiserGroups);
    }

    /** @test */
    public function validateArray()
    {
        $data = ['some' => 'data'];
        $assertions = ['some' => 'assertions'];
        $this->validator->validateArray($data, $assertions)->shouldBeCalled();

        $this->sut->validateArray($data, $assertions);
    }
}
