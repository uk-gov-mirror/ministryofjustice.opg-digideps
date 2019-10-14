<?php

namespace Tests\AppBundle\Factory;

use AppBundle\Entity\Organisation;
use AppBundle\Factory\OrganisationFactory;
use PHPUnit\Framework\TestCase;

class OrganisationFactoryTest extends TestCase
{
    /** @var OrganisationFactory */
    private $factory;

    /** @var string[] */
    private $sharedDomains;

    protected function setUp(): void
    {
        $this->sharedDomains = ['foo.com', 'bar.co.uk', 'example.com'];

        $this->factory = new OrganisationFactory($this->sharedDomains);
    }

    /**
     * @test
     * @dataProvider getEmailVariations
     * @param $fullEmail
     * @param $expectedEmailIdentifier
     */
    public function createFromFullEmail_determinesEmailIdentiferFromTheFullGivenEmail(
        $fullEmail,
        $expectedEmailIdentifier,
        $isPublicDomain
    )
    {
        $organisation = $this->factory->createFromFullEmail('Org Name', $fullEmail, true);

        $this->assertInstanceOf(Organisation::class, $organisation);
        $this->assertEquals('Org Name', $organisation->getName());
        $this->assertEquals($expectedEmailIdentifier, $organisation->getEmailIdentifier());
        $this->assertTrue($organisation->isActivated());
        $this->assertEquals($isPublicDomain, $organisation->isPublicDomain());
    }

    /**
     * @return array
     */
    public function getEmailVariations(): array
    {
        return [
            ['fullEmail' => 'name@foo.com', 'expectedEmailIdentifier' => 'name@foo.com', 'isPublicDomain' => true],
            ['fullEmail' => 'name@Bar.co.uk', 'expectedEmailIdentifier' => 'name@bar.co.uk', 'isPublicDomain' => true],
            ['fullEmail' => 'name@private.com', 'expectedEmailIdentifier' => 'private.com', 'isPublicDomain' => false],
            ['fullEmail' => 'main-contact@private.com', 'expectedEmailIdentifier' => 'private.com', 'isPublicDomain' => false]
        ];
    }

    /**
     * @test
     */
    public function createFromEmailIdentifier_createsOrganisationUsingGivenArgAsEmailIdentifier()
    {
        $organisation = $this->factory->createFromEmailIdentifier('Org Name', 'Foo.Com', false);

        $this->assertInstanceOf(Organisation::class, $organisation);
        $this->assertEquals('Org Name', $organisation->getName());
        $this->assertEquals('foo.com', $organisation->getEmailIdentifier());
        $this->assertFalse($organisation->isActivated());
    }

    /**
     * @test
     * @dataProvider getInvalidInputs
     * @param $name
     * @param $emailIdentifier
     */
    public function createFromFullEmail_throwsExceptionIfGivenBadData($name, $emailIdentifier)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->createFromFullEmail($name, $emailIdentifier);
    }


    /**
     * @test
     * @dataProvider getInvalidInputs
     * @param $name
     * @param $emailIdentifier
     */
    public function createFromEmailIdentifier_throwsExceptionIfGivenBadData($name, $emailIdentifier)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->createFromEmailIdentifier($name, $emailIdentifier);
    }

    /**
     * @return array
     */
    public function getInvalidInputs(): array
    {
        return [
            ['name' => '', 'emailIdentifier' => 'f@test.com'],
            ['name' => 'name', 'emailIdentifier' => ''],
        ];
    }
}
