<?php declare(strict_types=1);


namespace AppBundle\TestHelpers;

use AppBundle\Entity\NamedDeputy;
use Faker;

class NamedDeputyHelper
{
    public static function createNamedDeputy()
    {
        $faker = Faker\Factory::create();

        return (new NamedDeputy())
            ->setFirstname($faker->firstName)
            ->setLastname($faker->lastName)
            ->setEmail1($faker->safeEmail)
            ->setPhoneMain($faker->phoneNumber)
            ->setId(1);
    }
}