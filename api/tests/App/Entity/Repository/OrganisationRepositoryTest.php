<?php

namespace Tests\App\Entity\Repository;

use App\Entity\Repository\OrganisationRepository;
use App\Entity\Organisation;
use DateTime;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Fixtures;

use function Symfony\Component\String\s;

class OrganisationRepositoryTest extends WebTestCase
{
    /**
     * @var OrganisationRepository
     */
    private $sut;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('em');
        $this->fixtures = new Fixtures($this->em);

        $metaClass = self::prophesize(ClassMetadata::class);
        $metaClass->name = Organisation::class;

        $this->sut = new OrganisationRepository($this->em, $metaClass->reveal());

        $purger = new ORMPurger($this->em);
        $purger->purge();
    }

    /** @test */
    public function testGetAllArray()
    {
        $orgs = $this->fixtures->createOrganisations(5);
        $this->em->flush();

        $allOrgs = $this->sut->getAllArray();

        self::assertCount(5, $allOrgs);
    }

    /** @test */
    public function testGetNonDeletedArray()
    {
        $orgs = $this->fixtures->createOrganisations(5);
        $this->em->flush();

        $allOrgs = $this->sut->getNonDeletedArray();
        self::assertCount(5, $allOrgs);

        $this->fixtures->deleteOrganisation($orgs[0]->getId());
        $this->em->flush();

        $nonDeletedOrgs = $this->sut->getNonDeletedArray();
        self::assertCount(4, $nonDeletedOrgs);
    }

    /** @test */
    public function testHasActiveEntities()
    {
        $orgs = $this->fixtures->createOrganisations(3);
        $user = $this->fixtures->createUser()->setRoleName(\App\Entity\User::ROLE_PA);
        $clientActive = $this->fixtures->createClient($user);
        $clientArchived = $this->fixtures->createClient($user, ['setArchivedAt' => new \DateTime()]);
        $clientArchived2 = $this->fixtures->createClient($user, ['setArchivedAt' => new \DateTime()]);
        $clientDeleted = $this->fixtures->createClient($user, ['setDeletedAt' => new \DateTime()]);
        $clientDeleted2 = $this->fixtures->createClient($user, ['setDeletedAt' => new \DateTime()]);


        $this->em->flush();

        //Org1 with no entities should have no active entities.
        $result = $this->sut->hasActiveEntities($orgs[0]->getId());
        self::assertFalse($result);

        //Org1 with 1 soft deleted client should have no active entities.
        $this->fixtures->addClientToOrganisation($clientDeleted->getId(), $orgs[0]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[0]->getId());
        self::assertFalse($result);

        //Org1 with 1 soft deleted client
        //And 1 archived client
        //Should have no active entities.
        $this->fixtures->addClientToOrganisation($clientArchived->getId(), $orgs[0]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[0]->getId());
        self::assertFalse($result);

        //Org1 with 1 soft deleted client
        //And 1 archived client
        //And 1 active client
        //Should have active entities.
        $this->fixtures->addClientToOrganisation($clientActive->getId(), $orgs[0]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[0]->getId());
        self::assertTrue($result);

        //Org2 with 1 user should have active entities.
        $this->fixtures->addUserToOrganisation($user->getId(), $orgs[1]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[1]->getId());
        self::assertTrue($result);

        //Org2 with 1 user
        //And 1 soft deleted client
        //should have active entities.
        $this->fixtures->addClientToOrganisation($clientDeleted2->getId(), $orgs[1]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[1]->getId());
        self::assertTrue($result);

        //Org2 with 1 user
        //And 1 soft deleted client
        //And 1 archived client
        //should have active entities.
        $this->fixtures->addClientToOrganisation($clientArchived2->getId(), $orgs[1]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[1]->getId());
        self::assertTrue($result);
    }

    /** @test */
    public function testFindByEmailIdentifier()
    {
        $orgs = $this->fixtures->createOrganisations(1);
        $this->em->flush();

        $result = $this->sut->findByEmailIdentifier($orgs[0]->getEmailIdentifier());
        self::assertEquals($orgs[0], $result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->em->close();
        $this->em = null;
    }
}