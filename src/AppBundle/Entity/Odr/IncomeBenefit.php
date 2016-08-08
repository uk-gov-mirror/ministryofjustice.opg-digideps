<?php

namespace AppBundle\Entity\Odr;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * @Assert\Callback(methods={"moreDetailsValidate"}, groups={"odr-state-benefits"})
 */
class IncomeBenefit
{
//    public static $stateBenefitsKeys = [
//        'contributionsBasedAllowance' => false,
//        'incomeSupportPensionGuaranteeCredit' => false,
//        'incomerelatedEmploymentSupportAllowance' => false,
//        'incomebasedJobSeekerAllowance' => false,
//        'housingBenefit' => false,
//        'universalCredit' => false,
//        'severeDisablementAllowance' => false,
//        'disabilityLivingAllowance' => false,
//        'attendanceAllowance' => false,
//        'prsonalIndependencePayment' => false,
//        'workingChildTaxCredits' => false,
//        'otherBenefits' => true,
//    ];

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"odr-income-benefit"})
     */
    private $typeId;

    /**
     * @var boolean
     *
     * @JMS\Type("boolean")
     * @JMS\Groups({"odr-income-benefit"})
     */
    private $present;

    /**
     * @var string
     * @JMS\Groups({"odr-income-benefit"})
     * @JMS\Type("boolean")
     */
    private $hasMoreDetails;

    /**
     * @var string
     * @JMS\Groups({"odr-income-benefit"})
     * @JMS\Type("string")
     */
    private $moreDetails;

    /**
     * IncomeBenefit constructor.
     * @param $typeId
     * @param bool $present
     * @param string $hasMoreDetails
     * @param string $moreDetails
     */
    public function __construct($typeId, $present, $hasMoreDetails = false, $moreDetails = null)
    {
        $this->typeId = $typeId;
        $this->present = $present;
        $this->hasMoreDetails = $hasMoreDetails;
        $this->moreDetails = $moreDetails;
    }

    /**
     * @return mixed
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param mixed $typeId
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;
    }

    /**
     * @return boolean
     */
    public function isPresent()
    {
        return $this->present;
    }

    /**
     * @param boolean $present
     */
    public function setPresent($present)
    {
        $this->present = $present;
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

    /**
     * flag moreDetails invalid if amount is given and moreDetails is empty
     * flag amount invalid if moreDetails is given and amount is empty.
     *
     * @param ExecutionContextInterface $context
     */
    public function moreDetailsValidate(ExecutionContextInterface $context)
    {
        // if the transaction required no moreDetails, no validation is needed
        if (!$this->getHasMoreDetails()) {
            return;
        }

        $isPresent = $this->isPresent();
        $hasMoreDetails = trim($this->getMoreDetails(), " \n") ? true : false;

        if ($isPresent && !$hasMoreDetails) {
            $context->addViolationAt('moreDetails', 'odr.incomeBenefit.moreDetails.notBlank');
        }

        if (!$isPresent && $hasMoreDetails) {
            $context->addViolationAt('present', 'odr.incomeBenefit.present.notBlank');
        }
    }

}
