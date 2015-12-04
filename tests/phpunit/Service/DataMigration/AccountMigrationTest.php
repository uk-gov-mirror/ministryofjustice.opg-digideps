<?php

namespace AppBundle\Service\DataMigration;

use PDO;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountMigrationTest extends WebTestCase
{

    public function setUp()
    {
        // import database at version 4 with some old account and transactions
        $export = "export PGHOST=postgres; export PGPASSWORD=api; export PGDATABASE=digideps_unit_test; export PGUSER=api;";
        exec("$export psql -U api -c 'DROP SCHEMA IF EXISTS public cascade'", $out1);
        exec("$export psql -U api < ".__DIR__."/oldTransactions.sql" , $out2);

        //migrate from version 47 (that will test migration too)
        exec('php app/console doctrine:migrations:migrate --no-interaction --env=test');

//        $this->assertCount(211, $out2, "cannot import SQL file for account migration testing");

        $client = self::createClient([ 'environment' => 'test',
            'debug' => true ]);
        $em = $client->getContainer()->get('em');

        $this->am = new AccountMigration($em->getConnection());
        $reports = $this->am->getReports();
        file_put_contents(__DIR__.'/res.txt', print_r($reports, true));

        $this->assertCount(2, $reports, '#reports mismatch');

        //report 1
        $report = $reports[1];
        $this->assertCount(0, $report['transactions_new']);
        $this->assertEquals(['in'=>0, 'out'=>0], $report['transactions_new_sum']);
        $this->assertCount(1, $report['accounts']);
        // 1st account
        $account = $report['accounts'][1];
        $this->assertCount(40, $account['transactions_old']);
        $this->assertEquals(2.00, $account['transactions_old']['attendance_allowance']['amount']);
        $this->assertEquals(190.0, $account['transactions_old_sum']['in']);
        $this->assertEquals(630.0, $account['transactions_old_sum']['out']);

        //report 2
        $report = $reports[2];
        $this->assertCount(0, $report['transactions_new']);
        $this->assertEquals(0, $report['transactions_new_sum']['in']);
        $this->assertEquals(0, $report['transactions_new_sum']['out']);
        $this->assertCount(2, $report['accounts']);
        // 1st account
        $account = $report['accounts'][2];
        $this->assertCount(40, $account['transactions_old']);
        $this->assertEquals(101.1, $account['transactions_old_sum']['in'], '', 0.1);
        $this->assertEquals(102, $account['transactions_old_sum']['out'], '', 0.1);
        // 2nd account
        $account = $report['accounts'][3];
        $this->assertCount(40, $account['transactions_old']);
        $this->assertEquals(91, $account['transactions_old_sum']['in'], '', 0.1);
        $this->assertEquals(92, $account['transactions_old_sum']['out']);
    }

    public function testMigrateAccounts()
    {
        $this->am->migrateAccounts();


    }

//    public function tearDown()
//    {
//        exec('php app/console cache:clear --env=test');
//        exec('php app/console doctrine:query:sql "DROP SCHEMA IF EXISTS public cascade; CREATE SCHEMA IF NOT EXISTS public;" --env=test');
//        exec('php app/console doctrine:migrations:migrate --no-interaction --env=test');
//        exec('php app/console doctrine:schema:validate --env=test');
//        exec('php app/console digideps:add-user deputy@example.org --firstname=test --lastname=deputy --role=2 --password=Abcd1234 --env=test');
//        exec('php app/console digideps:add-user admin@example.org --firstname=test --lastname=admin  --role=1 --password=Abcd1234 --env=test');
//    }

}