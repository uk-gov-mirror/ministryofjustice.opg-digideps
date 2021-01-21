<?php

namespace App\Entity\Report;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;

class Debt
{
    /**
     * @JMS\Type("string")
     * @JMS\Groups({"debt"})
     */
    private $debtTypeId;

    /**
     * @var decimal
     *
     * @JMS\Type("string")
     * @JMS\Groups({"debt"})
     * @Assert\Type(type="numeric", message="debt.amount.notNumeric", groups={"debts"})
     * @Assert\Range(min=0, max=100000000000, minMessage = "debt.amount.minMessage", maxMessage = "debt.amount.maxMessage", groups={"debts"})
     */
    private $amount;

    /**
     * @var string
     * @JMS\Groups({"debt"})
     * @JMS\Type("boolean")
     * @AppAssert\TextNoSpecialCharacters
     */
    private $hasMoreDetails;

    /**
     * @var string
     * @JMS\Groups({"debt"})
     * @JMS\Type("string")
     *
     * @Assert\NotBlank(message="debt.moreDetails.notEmpty", groups={"debts-more-details"})
     * @AppAssert\TextNoSpecialCharacters(groups={"debts-more-details"})
     */
    private $moreDetails;

    /**
     * Debt constructor.
     *
     * @param $debtTypeId
     * @param decimal $amount
     * @param string  $hasMoreDetails
     * @param string  $moreDetails
     */
    public function __construct($debtTypeId, $amount, $hasMoreDetails, $moreDetails)
    {
        $this->debtTypeId = $debtTypeId;
        $this->amount = $amount;
        $this->hasMoreDetails = $hasMoreDetails;
        $this->moreDetails = $moreDetails;
    }

    /**
     * @return array
     */
    public static function getDebtTypeIds()
    {
        return self::$debtTypeIds;
    }

    /**
     * @param array $debtTypeIds
     */
    public static function setDebtTypeIds($debtTypeIds)
    {
        self::$debtTypeIds = $debtTypeIds;
    }

    /**
     * @return mixed
     */
    public function getDebtTypeId()
    {
        return $this->debtTypeId;
    }

    /**
     * @param mixed $debtTypeId
     */
    public function setDebtTypeId($debtTypeId)
    {
        $this->debtTypeId = $debtTypeId;
    }

    /**
     * @return decimal
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param decimal $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getHasMoreDetails()
    {
        return $this->hasMoreDetails;
    }

    /**
     * @param string $hasMoreDetails
     */
    public function setHasMoreDetails($hasMoreDetails)
    {
        $this->hasMoreDetails = $hasMoreDetails;
    }

    /**
     * @return string
     */
    public function getMoreDetails()
    {
        return $this->moreDetails;
    }

    /**
     * @param string $moreDetails
     */
    public function setMoreDetails($moreDetails)
    {
        $this->moreDetails = $moreDetails;
    }
}
