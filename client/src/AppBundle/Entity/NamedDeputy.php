<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Traits\AddressTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Users.
 *
 * @ORM\Table(name="named_deputy", indexes={@ORM\Index(name="named_deputy_no_idx", columns={"deputy_no"})})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Repository\NamedDeputyRepository")
 */
class NamedDeputy
{
    use AddressTrait;

    /**
     * @var int
     * @JMS\Type("integer")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     */
    private $id;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     */
    private $deputyNo;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     *
     */
    private $firstname;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     */
    private $lastname;

    /**
     * @var string
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     * @JMS\Type("string")
     *
     */
    private $email1;

    /**
     * @var string
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     * @JMS\Type("string")
     *
     */
    private $email2;

    /**
     * @var string
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     * @JMS\Type("string")
     *
     */
    private $email3;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     */
    private $depAddrNo;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     */
    private $address1;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     */
    private $address2;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     */
    private $address3;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     */
    private $address4;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     */
    private $address5;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     */
    private $addressPostcode;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report-submitted-by", "named-deputy"})
     */
    private $addressCountry;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeputyNo()
    {
        return $this->deputyNo;
    }

    /**
     * @param string $deputyNo
     *
     * @return $this
     */
    public function setDeputyNo($deputyNo)
    {
        $this->deputyNo = User::padDeputyNumber($deputyNo);
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     *
     * @return $this
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     *
     * @return $this
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @return string
     *
     * @return $this
     */
    public function getEmail1()
    {
        return $this->email1;
    }

    /**
     * @param string $email1
     *
     * @return $this
     */
    public function setEmail1($email1)
    {
        $this->email1 = $email1;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail2()
    {
        return $this->email2;
    }

    /**
     * @param string $email2
     *
     * @return $this
     */
    public function setEmail2($email2)
    {
        $this->email2 = $email2;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail3()
    {
        return $this->email3;
    }

    /**
     * @param string $email3
     *
     * @return $this
     */
    public function setEmail3($email3)
    {
        $this->email3 = $email3;
        return $this;
    }

    /**
     * @return string
     */
    public function getDepAddrNo()
    {
        return $this->depAddrNo;
    }

    /**
     * @param string $depAddrNo
     *
     * @return $this
     */
    public function setDepAddrNo($depAddrNo)
    {
        $this->depAddrNo = User::padDeputyNumber($depAddrNo);
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * @param string $address1
     * @return $this
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param string $address2
     * @return $this
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress3()
    {
        return $this->address3;
    }

    /**
     * @param string $address3
     * @return $this
     */
    public function setAddress3($address3)
    {
        $this->address3 = $address3;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress4()
    {
        return $this->address4;
    }

    /**
     * @param string $address4
     * @return $this
     */
    public function setAddress4($address4)
    {
        $this->address4 = $address4;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress5()
    {
        return $this->address5;
    }

    /**
     * @param string $address5
     * @return $this
     */
    public function setAddress5($address5)
    {
        $this->address5 = $address5;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddressPostcode()
    {
        return $this->addressPostcode;
    }

    /**
     * @param string $addressPostcode
     * @return $this
     */
    public function setAddressPostcode($addressPostcode)
    {
        $this->addressPostcode = $addressPostcode;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddressCountry()
    {
        return $this->addressCountry;
    }

    /**
     * @param $addressCountry
     * @return $this
     */
    public function setAddressCountry($addressCountry)
    {
        $this->addressCountry = $addressCountry;
        return $this;
    }
}
