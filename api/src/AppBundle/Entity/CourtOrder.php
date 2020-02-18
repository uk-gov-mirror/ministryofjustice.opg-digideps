<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Report\Report;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Court Order
 *
 * @ORM\Table(name="court_order")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Repository\CourtOrderRepository")
 */
class CourtOrder
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="court_order_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var Client
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Client", inversedBy="courtOrders")
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organisation", inversedBy="courtOrders")
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
    public function getDeputies(): ArrayCollection
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
    public function getReports(): ArrayCollection
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
}
