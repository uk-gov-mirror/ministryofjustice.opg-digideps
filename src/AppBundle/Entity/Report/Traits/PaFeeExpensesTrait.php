<?php

namespace AppBundle\Entity\Report\Traits;

use AppBundle\Entity\Report\Fee;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

trait PaFeeExpensesTrait
{

    /**
     * @var Fee[]
     *
     * @JMS\Groups({"fee"})
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Report\Fee", mappedBy="report", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $fees;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"fee"})
     * @ORM\Column(name="reason_for_no_fees", type="text", nullable=true)
     */
    private $reasonForNoFees;

    /**
     * @return Fee[]
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * @param Fee $fee
     */
    public function addFee(Fee $fee)
    {
        if (!$this->fees->contains($fee)) {
            $this->fees->add($fee);
        }

        return $this;
    }

    /**
     * @param string $typeId
     *
     * @return Fee
     */
    public function getFeeByTypeId($typeId)
    {
        return $this->getFees()->filter(function (Fee $fee) use ($typeId) {
            return $fee->getFeeTypeId() == $typeId;
        })->first();
    }

    /**
     * @return mixed
     */
    public function getReasonForNoFees()
    {
        return $this->reasonForNoFees;
    }

    /**
     * @param mixed $reasonForNoFees
     */
    public function setReasonForNoFees($reasonForNoFees)
    {
        $this->reasonForNoFees = $reasonForNoFees;
    }

    /**
     * Get fee total value.
     *
     * @JMS\VirtualProperty
     * @JMS\Type("double")
     * @JMS\SerializedName("fees_total")
     * @JMS\Groups({"fee"})
     *
     * @return float
     */
    public function getFeesTotal()
    {
        $ret = 0;
        foreach ($this->getFees() as $fee) {
            $ret += $fee->getAmount();
        }

        return $ret;
    }

    /**
     * @return Fee[]
     */
    public function getFeesWithValidAmount()
    {
        $fees = $this->getFees()->filter(function ($fee) {
            return !empty($fee->getAmount());
        });

        return $fees;
    }

    /**
     * Implement the report.hasFees based on the content of fees and reaons for no fees
     * Alternative to have a column
     *
     * @JMS\VirtualProperty
     * @JMS\Type("string")
     * @JMS\SerializedName("has_fees")
     * @JMS\Groups({"fee"})
     *
     * @return float
     */
    public function getHasFees()
    {
        // never set -> return null
        if (0 === count($this->getFeesWithValidAmount()) && $this->getReasonForNoFees() === null) {
            return null;
        }

        return $this->getReasonForNoFees() ? 'no' : 'yes';
    }
}
