<?php

namespace AppBundle\Entity\Odr\Traits;

use AppBundle\Entity\Client;
use AppBundle\Entity\Odr\IncomeBenefit;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

trait OdrIncomeBenefitTrait
{

    /**
     * @JMS\Type("array<AppBundle\Entity\Odr\IncomeBenefit>")
     * @JMS\Groups({"odr-state-benefits"})
     *
     * @var IncomeBenefit[]
     */
    private $stateBenefits;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"odr-income-pension"})
     * @Assert\NotBlank(message="odr.incomeBenefit.receiveStatePension.notBlank", groups={"receive-state-pension"})
     */
    private $receiveStatePension;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"odr-income-pension"})
     * @Assert\NotBlank(message="odr.incomeBenefit.receiveOtherIncome.notBlank", groups={"receive-other-income"})
     */
    private $receiveOtherIncome;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"odr-income-pension"})
     * @Assert\NotBlank(message="odr.incomeBenefit.receiveOtherIncomeDetails.notBlank", groups={"receive-other-income-yes"})
     */
    private $receiveOtherIncomeDetails;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"odr-income-damages"})
     * @Assert\NotBlank(message="odr.incomeBenefit.expectCompensationDamages.notBlank", groups={"expect-compensation-damage"})
     */
    private $expectCompensationDamages;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"odr-income-damages"})
     * @Assert\NotBlank(message="odr.incomeBenefit.expectCompensationDamagesDetails.notBlank", groups={"expect-compensation-damage-yes"})
     */
    private $expectCompensationDamagesDetails;

    /**
     * @JMS\Type("array<AppBundle\Entity\Odr\IncomeBenefit>")
     * @JMS\Groups({"odr-one-off"})
     *
     * @var IncomeBenefit[]
     */
    private $oneOff;

    /**
     * @return IncomeBenefit[]
     */
    public function getStateBenefits()
    {
        return $this->stateBenefits;
    }

    /**
     * @param IncomeBenefit $stateBenefits
     * @return Odr
     */
    public function setStateBenefits($stateBenefits)
    {
        $this->stateBenefits = $stateBenefits;
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiveStatePension()
    {
        return $this->receiveStatePension;
    }

    /**
     * @param string $receiveStatePension
     * @return Odr
     */
    public function setReceiveStatePension($receiveStatePension)
    {
        $this->receiveStatePension = $receiveStatePension;
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiveOtherIncome()
    {
        return $this->receiveOtherIncome;
    }

    /**
     * @param string $receiveOtherIncome
     * @return Odr
     */
    public function setReceiveOtherIncome($receiveOtherIncome)
    {
        $this->receiveOtherIncome = $receiveOtherIncome;
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiveOtherIncomeDetails()
    {
        return $this->receiveOtherIncomeDetails;
    }

    /**
     * @param string $receiveOtherIncomeDetails
     */
    public function setReceiveOtherIncomeDetails($receiveOtherIncomeDetails)
    {
        $this->receiveOtherIncomeDetails = $receiveOtherIncomeDetails;

        return $this;
    }

    /**
     * @return string
     */
    public function getExpectCompensationDamages()
    {
        return $this->expectCompensationDamages;
    }

    /**
     * @param string $expectCompensationDamages
     * @return OdrIncomeBenefitTrait
     */
    public function setExpectCompensationDamages($expectCompensationDamages)
    {
        $this->expectCompensationDamages = $expectCompensationDamages;
        return $this;
    }

    /**
     * @return string
     */
    public function getExpectCompensationDamagesDetails()
    {
        return $this->expectCompensationDamagesDetails;
    }

    /**
     * @param string $expectCompensationDamagesDetails
     * @return OdrIncomeBenefitTrait
     */
    public function setExpectCompensationDamagesDetails($expectCompensationDamagesDetails)
    {
        $this->expectCompensationDamagesDetails = $expectCompensationDamagesDetails;
        return $this;
    }

    /**
     * @return IncomeBenefit[]
     */
    public function getOneOff()
    {
        return $this->oneOff;
    }

    /**
     * @param IncomeBenefit[] $oneOff
     * @return OdrIncomeBenefitTrait
     */
    public function setOneOff($oneOff)
    {
        $this->oneOff = $oneOff;
        return $this;
    }

    /**
     * @param IncomeBenefit[] $elements
     *
     * @return int
     */
    public function countRecordsPresent($elements)
    {
        if (empty($elements) || !is_array($elements)) {
            return 0;
        }

        return count(array_filter($elements, function($st) {
            return $st instanceof IncomeBenefit && $st->isPresent();
        }));
    }

    /**
     * @return string not-started/incomplete/done
     */
    public function incomeBenefitsStatus()
    {
        $stCount = $this->countRecordsPresent($this->getStateBenefits());
        $q1 = $this->getReceiveStatePension();
        $q2 = $this->getReceiveOtherIncome();
        $q3 = $this->getExpectCompensationDamages();
        $ooCount = $this->countRecordsPresent($this->getOneOff());

        if ($stCount === 0
            && $q1 == null && $q2 == null && $q3 == null
            && $ooCount === 0
        ) {
            return 'not-started';
        }

        if ($stCount > 0
            && $q1 != null && $q2 != null && $q3 != null
            && $ooCount > 0
        ) {
            return 'done';
        }

        return 'incomplete';
    }

}
