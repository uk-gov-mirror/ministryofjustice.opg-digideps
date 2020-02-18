<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Report\Report;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Court Order
 *
 * @ORM\Table(name="court_order")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Repository\CourtOrderRepository")
 */
class CourtOrder
{
    const SUBTYPE_HW = 'hw';
    const SUBTYPE_PFA = 'pfa';

    const LEVEL_MINIMAL = 'MINIMAL';
    const LEVEL_GENERAL = 'GENERAL';

    /**
     * @var int
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="court_order_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     * @Assert\Choice({CourtOrder::SUBTYPE_HW, CourtOrder::SUBTYPE_PFA})
     * @ORM\Column(name="type", type="string", nullable=false)
     */
    private $type;

    /**
     * @var string
     * @Assert\Choice({CourtOrder::LEVEL_MINIMAL, CourtOrder::LEVEL_GENERAL})
     * @ORM\Column(name="supervision_level", type="string", nullable=false)
     */
    private $supervisionLevel;

    /**
     * @var DateTime
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var Client
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Client", inversedBy="courtOrders", cascade={"persist"})
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\User", inversedBy="courtOrders")
     * @ORM\JoinTable(name="court_order_deputies")
     */
    private $deputies;

    /**
     * @var Organisation
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organisation", inversedBy="courtOrders", cascade={"persist"})
     * @ORM\JoinColumn(name="organisation_id", referencedColumnName="id")
     */
    private $organisation;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Report\Report", mappedBy="courtOrder")
     */
    private $reports;

    public function __construct()
    {
        $this->deputies = new ArrayCollection();
        $this->reports = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return CourtOrder
     */
    public function setId(int $id): CourtOrder
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     * @return CourtOrder
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getDeputies(): iterable
    {
        return $this->deputies;
    }

    /**
     * @param User $deputy
     * @return CourtOrder
     */
    public function addDeputy(User $deputy): CourtOrder
    {
        if (!$this->deputies->contains($deputy)) {
            $this->deputies->add($deputy);
        }

        return $this;
    }

    /**
     * @return Organisation
     */
    public function getOrganisation(): Organisation
    {
        return $this->organisation;
    }

    /**
     * @param Organisation $organisation
     * @return CourtOrder
     */
    public function setOrganisation(Organisation $organisation): CourtOrder
    {
        $this->organisation = $organisation;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getReports(): iterable
    {
        return $this->reports;
    }

    public function addReport(Report $report)
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
        }

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): CourtOrder
    {
        if (!in_array($type, [self::SUBTYPE_HW, self::SUBTYPE_PFA])) {
            throw new InvalidArgumentException('Invalid CourtOrder type');
        }

        $this->type = $type;

        return $this;
    }

    public function getSupervisionLevel(): string
    {
        return $this->supervisionLevel;
    }

    public function setSupervisionLevel(string $supervisionLevel): CourtOrder
    {
        if (!in_array($supervisionLevel, [self::LEVEL_GENERAL, self::LEVEL_MINIMAL])) {
            throw new InvalidArgumentException('Invalid CourtOrder supervision level');
        }

        $this->supervisionLevel = $supervisionLevel;

        return $this;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): CourtOrder
    {
        $this->date = $date;

        return $this;
    }
}
