<?php declare(strict_types=1);


namespace AppBundle\TestHelpers;

use AppBundle\Entity\Client;
use AppBundle\Entity\Report\Report;
use DateTime;
use Faker;

class ClientHelpers
{
    /**
     * @param Report|null $report
     * @return Client
     */
    public static function createClient(?Report $report = null): Client
    {
        $faker = Faker\Factory::create();

        $client = (new Client())
            ->setCaseNumber(self::createValidCaseNumber())
            ->setCourtDate(new DateTime())
            ->setEmail($faker->safeEmail)
            ->setFirstname($faker->firstName)
            ->setLastname($faker->lastName)
            ->setId(1);

        if ($report) {
            $client->addReport($report);
        }

        return $client;
    }

    /**
     * @param Report|null $report
     * @return Client
     */
    public static function createClientWithUsers(?Report $report = null): Client
    {
        $user = UserHelpers::createUser();
        return (self::createClient($report))->addUser($user);
    }

    /**
     * Sirius has a modulus 11 validation check on case references (because casrec.) which we should adhere to
     * to make sure integration tests create data that is in the correct format.
     */
    public static function createValidCaseNumber()
    {
        $ref = '';
        $sum = 0;

        foreach ([3, 4, 7, 5, 8, 2, 4] as $constant) {
            $value = mt_rand(0, 9);
            $ref .= $value;
            $sum += $value * $constant;
        }

        $checkbit = (11 - ($sum % 11)) % 11;

        if ($checkbit === 10) {
            $checkbit = 'T';
        }

        return $ref . $checkbit;
    }
}