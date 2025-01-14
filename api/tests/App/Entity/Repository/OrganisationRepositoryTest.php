<?php

namespace Tests\App\Entity\Repository;

use App\Entity\Organisation;
use App\Repository\OrganisationRepository;
use DateTime;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Fixtures;

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

        $this->em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->fixtures = new Fixtures($this->em);

        $metaClass = self::prophesize(ClassMetadata::class);
        $metaClass->name = Organisation::class;

        $this->sut = $this->em->getRepository(Organisation::class);

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
    public function testHasActiveEntities_no_entities_in_org_returns_false()
    {
        $orgs = $this->fixtures->createOrganisations(1);
        $this->em->flush();

        $result = $this->sut->hasActiveEntities($orgs[0]->getId());
        self::assertFalse($result);
    }

    /** @test */
    public function testHasActiveEntities_soft_deleted_client_in_org_returns_false()
    {
        $orgs = $this->fixtures->createOrganisations(1);
        $user = $this->fixtures->createUser()->setRoleName(\App\Entity\User::ROLE_PA);
        $clientDeleted = $this->fixtures->createClient($user, ['setDeletedAt' => new \DateTime()]);
        $this->em->flush();

        $this->fixtures->addClientToOrganisation($clientDeleted->getId(), $orgs[0]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[0]->getId());
        self::assertFalse($result);
    }

    /** @test */
    public function testHasActiveEntities_archived_client_in_org_returns_false()
    {
        $orgs = $this->fixtures->createOrganisations(1);
        $user = $this->fixtures->createUser()->setRoleName(\App\Entity\User::ROLE_PA);
        $clientArchived = $this->fixtures->createClient($user, ['setArchivedAt' => new \DateTime()]);
        $this->em->flush();

        $this->fixtures->addClientToOrganisation($clientArchived->getId(), $orgs[0]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[0]->getId());
        self::assertFalse($result);
    }

    /** @test */
    public function testHasActiveEntities_archived_and_soft_deleted_clients_in_org_returns_false()
    {
        $orgs = $this->fixtures->createOrganisations(1);
        $user = $this->fixtures->createUser()->setRoleName(\App\Entity\User::ROLE_PA);
        $clientDeleted = $this->fixtures->createClient($user, ['setDeletedAt' => new \DateTime()]);
        $clientArchived = $this->fixtures->createClient($user, ['setArchivedAt' => new \DateTime()]);
        $this->em->flush();

        $this->fixtures->addClientToOrganisation($clientDeleted->getId(), $orgs[0]->getId());
        $this->fixtures->addClientToOrganisation($clientArchived->getId(), $orgs[0]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[0]->getId());
        self::assertFalse($result);
    }

    /** @test */
    public function testHasActiveEntities_archived_and_soft_deleted_and_active_clients_in_org_returns_true()
    {
        $orgs = $this->fixtures->createOrganisations(1);
        $user = $this->fixtures->createUser()->setRoleName(\App\Entity\User::ROLE_PA);
        $clientActive = $this->fixtures->createClient($user);
        $clientDeleted = $this->fixtures->createClient($user, ['setDeletedAt' => new \DateTime()]);
        $clientArchived = $this->fixtures->createClient($user, ['setArchivedAt' => new \DateTime()]);
        $this->em->flush();

        $this->fixtures->addClientToOrganisation($clientActive->getId(), $orgs[0]->getId());
        $this->fixtures->addClientToOrganisation($clientDeleted->getId(), $orgs[0]->getId());
        $this->fixtures->addClientToOrganisation($clientArchived->getId(), $orgs[0]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[0]->getId());
        self::assertTrue($result);
    }

    /** @test */
    public function testHasActiveEntities_archived_and_soft_deleted_clients_and_user_in_org_returns_true()
    {
        $orgs = $this->fixtures->createOrganisations(1);
        $user = $this->fixtures->createUser()->setRoleName(\App\Entity\User::ROLE_PA);
        $clientDeleted = $this->fixtures->createClient($user, ['setDeletedAt' => new \DateTime()]);
        $clientArchived = $this->fixtures->createClient($user, ['setArchivedAt' => new \DateTime()]);
        $this->em->flush();

        $this->fixtures->addUserToOrganisation($user->getId(), $orgs[0]->getId());
        $this->fixtures->addClientToOrganisation($clientDeleted->getId(), $orgs[0]->getId());
        $this->fixtures->addClientToOrganisation($clientArchived->getId(), $orgs[0]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[0]->getId());
        self::assertTrue($result);
    }

    /** @test */
    public function testHasActiveEntities_active_client_and_user_in_org_returns_true()
    {
        $orgs = $this->fixtures->createOrganisations(1);
        $user = $this->fixtures->createUser()->setRoleName(\App\Entity\User::ROLE_PA);
        $clientActive = $this->fixtures->createClient($user);
        $this->em->flush();

        $this->fixtures->addUserToOrganisation($user->getId(), $orgs[0]->getId());
        $this->fixtures->addClientToOrganisation($clientActive->getId(), $orgs[0]->getId());
        $this->em->flush();
        $result = $this->sut->hasActiveEntities($orgs[0]->getId());
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
