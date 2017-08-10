<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Report\Report;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Client.
 *
 * @ORM\Table(name="client", indexes={@ORM\Index(name="case_number_idx", columns={"case_number"})})
 * @ORM\Entity
 */
class Client
{
    /**
     * @var int
     *
     * @JMS\Groups({"related","basic", "client", "client-id"})
     * @JMS\Type("integer")
     * @JMS\Groups({"client"})
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="client_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @JMS\Groups({"user"})
     * @JMS\Accessor(getter="getUserIds")
     * @JMS\Type("array")
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\User", inversedBy="clients")
     * @ORM\JoinTable(name="deputy_case",
     *         joinColumns={@ORM\JoinColumn(name="client_id", referencedColumnName="id", onDelete="CASCADE")},
     *         inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")}
     *     )
     */
    private $users;

    /**
     * //TODO JMS "report" group is deprecated, use "client-reports" instead
     *
     * @JMS\Groups({"client-reports"})
     * @JMS\Type("array")
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Report\Report", mappedBy="client", cascade={"persist"})
     */
    private $reports;

    /**
     * @JMS\Groups({"basic", "odr", "odr_id"})
     * @JMS\Type("AppBundle\Entity\Odr\Odr")
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Odr\Odr", mappedBy="client", cascade={"persist"})
     **/
    private $odr;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"client", "client-case-number"})
     *
     * @var string
     *
     * @ORM\Column(name="case_number", type="string", length=20, nullable=true)
     */
    private $caseNumber;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"client"})
     *
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=60, nullable=true)
     */
    private $email;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"client"})
     *
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     */
    private $phone;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"client"})
     *
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=200, nullable=true)
     */
    private $address;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"client"})
     *
     * @var string
     *
     * @ORM\Column(name="address2", type="string", length=200, nullable=true)
     */
    private $address2;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"client"})
     *
     * @var string
     *
     * @ORM\Column(name="county", type="string", length=75, nullable=true)
     */
    private $county;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"client"})
     *
     * @var string
     *
     * @ORM\Column(name="postcode", type="string", length=10, nullable=true)
     */
    private $postcode;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"client"})
     *
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=10, nullable=true)
     */
    private $country;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"client", "client-name"})
     *
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=50, nullable=true)
     */
    private $firstname;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"client", "client-name"})
     *
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=50, nullable=true)
     */
    private $lastname;

    /**
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @JMS\Groups({"client"})
     *
     * @var \Date
     *
     * @ORM\Column(name="court_date", type="date", nullable=true)
     */
    private $courtDate;

    /**
     * @JMS\Exclude
     *
     * @var \DateTime
     *
     * @ORM\Column(name="last_edit", type="datetime", nullable=true)
     */
    private $lastedit;

    /**
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @JMS\Groups({"client"})
     *
     * @var \Date
     *
     * @ORM\Column(name="date_of_birth", type="date", nullable=true)
     */
    private $dateOfBirth;

    /**
     * @var ArrayCollection
     *
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Note>")
     * @JMS\Groups({"notes"})
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Note", mappedBy="client", cascade={"persist"})
     * @ORM\OrderBy({"createdOn"="DESC"})
     */
    private $notes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->reports = new ArrayCollection();
        $this->notes = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set caseNumber.
     *
     * @param string $caseNumber
     *
     * @return Client
     */
    public function setCaseNumber($caseNumber)
    {
        // normalise case number in order to understand if it's already used when registering and checking with CASREC
        $this->caseNumber = CasRec::normaliseCaseNumber($caseNumber);

        return $this;
    }

    /**
     * Get caseNumber.
     *
     * @return string
     */
    public function getCaseNumber()
    {
        return $this->caseNumber;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return Client
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set phone.
     *
     * @param string $phone
     *
     * @return Client
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set address.
     *
     * @param string $address
     *
     * @return Client
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set postcode.
     *
     * @param string $postcode
     *
     * @return Client
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * Get postcode.
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * Set firstname.
     *
     * @param string $firstname
     *
     * @return Client
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname.
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname.
     *
     * @param string $lastname
     *
     * @return Client
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname.
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set courtDate.
     *
     * @param \DateTime $courtDate
     *
     * @return Client
     */
    public function setCourtDate(\DateTime $courtDate = null)
    {
        $this->courtDate = $courtDate;

        return $this;
    }

    /**
     * Get courtDate.
     *
     * @return \DateTime
     */
    public function getCourtDate()
    {
        return $this->courtDate;
    }

    /**
     * Set lastedit.
     *
     * @param \DateTime $lastedit
     *
     * @return Client
     */
    public function setLastedit($lastedit)
    {
        $this->lastedit = $lastedit;

        return $this;
    }

    /**
     * Get lastedit.
     *
     * @return \DateTime
     */
    public function getLastedit()
    {
        return $this->lastedit;
    }

    /**
     * Add users.
     *
     * @param User $user
     *
     * @return Client
     */
    public function addUser(User $user)
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    /**
     * Remove users.
     *
     * @param User $users
     */
    public function removeUser(User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return array $userIds
     */
    public function getUserIds()
    {
        $userIds = [];

        if (!empty($this->users)) {
            foreach ($this->users as $user) {
                $userIds[] = $user->getId();
            }
        }

        return $userIds;
    }

    /**
     * Add reports.
     *
     * @param Report $reports
     *
     * @return Client
     */
    public function addReport(Report $report)
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
        }
        $report->setClient($this);

        return $this;
    }

    /**
     * Remove reports.
     *
     * @param Report $reports
     */
    public function removeReport(Report $reports)
    {
        $this->reports->removeElement($reports);
    }

    /**
     * Get reports.
     *
     * @return Report[]
     */
    public function getReports()
    {
        return $this->reports;
    }

    /**
     * Get report by end date
     *
     * @param \DateTime $endDate
     *
     * @return Report
     */
    public function getReportByEndDate(\DateTime $endDate)
    {
        return $this->reports->filter(function ($report) use ($endDate) {
            return $endDate->format('Y-m-d') == $report->getEndDate()->format('Y-m-d');
        })->first();
    }

    /**
     * Get un-submitted reports, ordered by most recently submitted first
     *
     *  //TODO refactor using OrderBy({"submitDate"="DESC"}) on client.reports
     *
     * @return ArrayCollection
     */
    public function getSubmittedReports()
    {
        $arrayIterator = $this->reports->filter(function ($report) {
            return $report->getSubmitted();
        })->getIterator();

        # Sort by submitted date so the most recently submitted are first
        $arrayIterator->uasort(function ($first, $second) {
            return $first->getSubmitDate() < $second->getSubmitDate() ? 1 : -1;
        });

        return new ArrayCollection(iterator_to_array($arrayIterator));
    }

    /**
     * Get un-submitted reports.
     *
     * @return ArrayCollection
     */
    public function getUnsubmittedReports()
    {
        return $this->reports->filter(function ($report) {
            return !$report->getSubmitted();
        });
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\Type("AppBundle\Entity\Report\Report")
     * @JMS\SerializedName("current_report")
     * @JMS\Groups({"current-report"})
     *
     * @return Report|null
     */
    public function getCurrentReport()
    {
        return $this->getUnsubmittedReports()->first() ?: null;
    }

    /**
     * @return array $reportIds
     */
    public function getReportIds()
    {
        $reportIds = [];

        if (!empty($this->reports)) {
            foreach ($this->reports as $report) {
                $reportIds[] = $report->getId();
            }
        }

        return $reportIds;
    }

    /**
     * @return mixed
     */
    public function getOdr()
    {
        return $this->odr;
    }

    /**
     * @param mixed $odr
     */
    public function setOdr($odr)
    {
        $this->odr = $odr;
    }

    /**
     * Return full name, e.g. Mr John Smith.
     */
    public function getFullName($space = '&nbsp;')
    {
        return $this->getFirstname() . $space . $this->getLastname();
    }

    /**
     * Set address2.
     *
     * @param string $address2
     *
     * @return Client
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;

        return $this;
    }

    /**
     * Get address2.
     *
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * Set county.
     *
     * @param string $county
     *
     * @return Client
     */
    public function setCounty($county)
    {
        $this->county = $county;

        return $this;
    }

    /**
     * Get county.
     *
     * @return string
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * Set country.
     *
     * @param string $country
     *
     * @return Client
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return bool
     */
    public function hasDetails()
    {
        return !empty($this->getAddress());
    }

    /**
     * @return \DateTime $dateOfBirth
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * @param \DateTime $dateOfBirth
     *
     * @return \AppBundle\Entity\User
     */
    public function setDateOfBirth(\DateTime $dateOfBirth = null)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param $notes
     *
     * @return $this
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }
}
