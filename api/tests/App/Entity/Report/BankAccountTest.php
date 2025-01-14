<?php

namespace Tests\App\Entity\Report;

use App\Entity\Report\BankAccount;
use PHPUnit\Framework\TestCase;

class BankAccountTest extends TestCase
{
    public function testSetterGetters()
    {
        $account = new BankAccount();
        $account->setLastEdit(new \DateTime('2015-01-01'));
        $this->assertEquals('123456', $account->setAccountNumber('123456')->getAccountNumber());
        $this->assertEquals('123456', $account->setBank('123456')->getBank());
        $this->assertEquals('123456', $account->setClosingBalance('123456')->getClosingBalance());
        $this->assertEquals('123456', $account->setOpeningBalance('123456')->getOpeningBalance());
    }
}
