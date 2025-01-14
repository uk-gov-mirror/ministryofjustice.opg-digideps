<?php

namespace App\FixtureFactory;

use App\Entity\Client;
use App\Entity\Organisation;
use App\Entity\User;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFactory
{
    /** @var UserPasswordEncoderInterface  */
    private $encoder;

    /**
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function create(array $data): User
    {
        $roleName = $this->convertRoleName($data['deputyType']);

        $user = (new User())
            ->setFirstname(isset($data['firstName']) ? $data['firstName'] : ucfirst($data['deputyType']) . ' Deputy ' . $data['id'])
            ->setLastname(isset($data['lastName']) ? $data['lastName'] : 'User')
            ->setEmail(isset($data['email']) ? $data['email'] : 'behat-' . strtolower($data['deputyType']) .  '-deputy-' . $data['id'] . '@publicguardian.gov.uk')
            ->setActive(true)
            ->setRegistrationDate(new \DateTime())
            ->setNdrEnabled(strtolower($data['ndr']) === 'enabled' ? true : false)
            ->setCoDeputyClientConfirmed(isset($data['codeputyEnabled']))
            ->setPhoneMain('07911111111111')
            ->setAddress1('Victoria Road')
            ->setAddressPostcode(isset($data['postCode']) ? $data['postCode'] : 'SW1')
            ->setAddressCountry('GB')
            ->setRoleName($roleName);

        if ($data['activated'] === 'true' || $data['activated'] === true) {
            $user->setPassword($this->encoder->encodePassword($user, 'DigidepsPass1234'));
        } else {
            $user->setActive(false);
        }

        return $user;
    }

    /**
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function createAdmin(array $data): User
    {
        $user = (new User())
            ->setFirstname(isset($data['firstName']) ? $data['firstName'] : ucfirst($data['adminType']) . ' Admin ' . $data['email'])
            ->setLastname(isset($data['lastName']) ? $data['lastName'] : 'User')
            ->setEmail($data['email'])
            ->setRegistrationDate(new \DateTime())
            ->setRoleName($data['adminType']);

        if ($data['activated'] === 'true') {
            $user->setPassword($this->encoder->encodePassword($user, 'DigidepsPass1234'))->setActive(true);
        }

        return $user;
    }

    /**
     * @param Organisation $organisation
     * @return User|void
     */
    public function createGenericOrgUser(Organisation $organisation)
    {
        $faker = Factory::create();

        $email = sprintf('%s.%s@%s', $faker->firstName, $faker->lastName, $organisation->getEmailIdentifier());
        $trimmedEmail = substr($email, 0, 59);

        $user = (new User())
            ->setFirstname($faker->firstName)
            ->setLastname($faker->lastName)
            ->setEmail($trimmedEmail)
            ->setActive(true)
            ->setRegistrationDate(new \DateTime())
            ->setNdrEnabled(false)
            ->setPhoneMain('07911111111111')
            ->setAddress1('Victoria Road')
            ->setAddressPostcode('SW1')
            ->setAddressCountry('GB')
            ->setRoleName('ROLE_PROF_TEAM_MEMBER');

        $user->setPassword($this->encoder->encodePassword($user, 'DigidepsPass1234'));

        return $user;
    }

    private function convertRoleName(string $roleName): string
    {
        switch ($roleName) {
            case 'LAY':
                return 'ROLE_LAY_DEPUTY';
            case 'AD':
                return 'ROLE_AD';
            case 'ADMIN':
                return 'ROLE_ADMIN';
            case 'PA_TEAM_MEMBER':
                return 'ROLE_PA_TEAM_MEMBER';
            case 'PA_ADMIN':
                return 'ROLE_PA_ADMIN';
            case 'PROF_TEAM_MEMBER':
                return 'ROLE_PROF_TEAM_MEMBER';
            case 'PROF_ADMIN':
                return 'ROLE_PROF_ADMIN';
            default:
                return 'ROLE_' . $roleName . '_NAMED';
        }
    }

    public function createCoDeputy(User $originalDeputy, Client $client, array $data)
    {
        $user2 = clone $originalDeputy;
        $user2->setLastname($user2->getLastname() . '-2')
            ->setEmail(
                sprintf(
                    'co-%s-deputy-%d@fixture.com',
                    strtolower($data['deputyType']),
                    mt_rand(1000, 99999)
                )
            )
            ->addClient($client)
            ->setActive($data['activated'])
            ->setRegistrationDate(new \DateTime())
            ->setCoDeputyClientConfirmed(true)
            ->setActive(true);

        if ($data['activated'] === 'true' || $data['activated'] === true) {
            $user2->setPassword($this->encoder->encodePassword($user2, 'DigidepsPass1234'));
        } else {
            $user2->setActive(false);
        }

        return $user2;
    }
}
