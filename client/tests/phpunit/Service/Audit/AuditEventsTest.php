<?php declare(strict_types=1);

namespace App\Service\Audit;

use App\Entity\User;
use App\Service\Time\DateTimeProvider;
use App\Service\Time\FakeClock;
use DateTime;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class AuditEventsTest extends TestCase
{
    /**
     * @test
     * @dataProvider startDateProvider
     */
    public function clientDischarged(?string $expectedStartDate, ?DateTime $actualStartDate): void
    {
        $now = new DateTime();
        /** @var ObjectProphecy|DateTimeProvider $dateTimeProvider */
        $dateTimeProvider = self::prophesize(DateTimeProvider::class);
        $dateTimeProvider->getDateTime()->shouldBeCalled()->willReturn($now);

        $expected = [
            'trigger' => 'ADMIN_BUTTON',
            'case_number' => '19348522',
            'discharged_by' => 'me@test.com',
            'deputy_name' => 'Bjork Gudmundsdottir',
            'discharged_on' => $now->format(DateTime::ATOM),
            'deputyship_start_date' => $expectedStartDate,
            'event' => 'CLIENT_DELETED',
            'type' => 'audit'
        ];

        $actual = (new AuditEvents($dateTimeProvider->reveal()))->clientDischarged(
            'ADMIN_BUTTON',
            '19348522',
            'me@test.com',
            'Bjork Gudmundsdottir',
            $actualStartDate
        );

        $this->assertEquals($expected, $actual);
    }

    public function startDateProvider()
    {
        return [
             'Start date present' => [
                 '2019-07-08T09:36:00+01:00',
                 new DateTime('2019-07-08T09:36', new \DateTimeZone('+0100'))
             ],
             'Null start date' => [null, null]
         ];
    }

    /**
     * @test
     * @dataProvider emailChangeProvider
     */
    public function userEmailChanged()
    {
        $now = new DateTime();
        /** @var ObjectProphecy|DateTimeProvider $dateTimeProvider */
        $dateTimeProvider = self::prophesize(DateTimeProvider::class);
        $dateTimeProvider->getDateTime()->shouldBeCalled()->willReturn($now);

        $expected = [
            'trigger' => 'ADMIN_USER_EDIT',
            'email_changed_from' => 'me@test.com',
            'email_changed_to' => 'you@test.com',
            'changed_on' => $now->format(DateTime::ATOM),
            'changed_by' => 'super-admin@email.com',
            'subject_full_name' => 'Panda Bear',
            'subject_role' => 'ROLE_LAY_DEPUTY',
            'event' => 'USER_EMAIL_CHANGED',
            'type' => 'audit'
        ];

        $actual = (new AuditEvents($dateTimeProvider->reveal()))->userEmailChanged(
            'ADMIN_USER_EDIT',
            'me@test.com',
            'you@test.com',
            'super-admin@email.com',
            'Panda Bear',
            'ROLE_LAY_DEPUTY'
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @dataProvider emailChangeProvider
     */
    public function clientEmailChanged(?string $oldEmail, ?string $newEmail)
    {
        $now = new DateTime();
        /** @var ObjectProphecy|DateTimeProvider $dateTimeProvider */
        $dateTimeProvider = self::prophesize(DateTimeProvider::class);
        $dateTimeProvider->getDateTime()->shouldBeCalled()->willReturn($now);

        $expected = [
            'trigger' => 'DEPUTY_USER_EDIT',
            'email_changed_from' => $oldEmail,
            'email_changed_to' => $newEmail,
            'changed_on' => $now->format(DateTime::ATOM),
            'changed_by' => 'super-admin@email.com',
            'subject_full_name' => 'Panda Bear',
            'subject_role' => 'CLIENT',
            'event' => 'CLIENT_EMAIL_CHANGED',
            'type' => 'audit'
        ];

        $actual = (new AuditEvents($dateTimeProvider->reveal()))->clientEmailChanged(
            'DEPUTY_USER_EDIT',
            $oldEmail,
            $newEmail,
            'super-admin@email.com',
            'Panda Bear'
        );

        $this->assertEquals($expected, $actual);
    }

    public function emailChangeProvider()
    {
        return [
            'Email changed' => ['me@test.com', 'you@test.com'],
            'Email removed' =>  ['me@test.com', null],
            'Email added' => [null, 'you@test.com']
        ];
    }

    /**
     * @test
     * @dataProvider roleChangedProvider
     */
    public function roleChanged(string $trigger, $changedFrom, $changedTo, $changedBy, $userChanged): void
    {
        $now = new DateTime();
        /** @var ObjectProphecy|DateTimeProvider $dateTimeProvider */
        $dateTimeProvider = self::prophesize(DateTimeProvider::class);
        $dateTimeProvider->getDateTime()->shouldBeCalled()->willReturn($now);

        $expected = [
            'trigger' => $trigger,
            'role_changed_from' => $changedFrom,
            'role_changed_to' => $changedTo,
            'changed_by' => $changedBy,
            'user_changed' => $userChanged,
            'changed_on' => $now->format(DateTime::ATOM),
            'event' => AuditEvents::EVENT_ROLE_CHANGED,
            'type' => 'audit'
        ];

        $actual = (new AuditEvents($dateTimeProvider->reveal()))->roleChanged(
            $trigger,
            $changedFrom,
            $changedTo,
            $changedBy,
            $userChanged
        );

        $this->assertEquals($expected, $actual);
    }

    public function roleChangedProvider()
    {
        return [
            'PA to LAY' => ['ADMIN_BUTTON', 'ROLE_PA', 'ROLE_LAY_DEPUTY', 'polly.jean.harvey@test.com', 't.amos@test.com'],
            'PROF to PA' => ['ADMIN_BUTTON', 'ROLE_PROF', 'ROLE_PA', 't.amos@test.com', 'polly.jean.harvey@test.com'],
        ];
    }

    /**
     * @test
     */
    public function userDeleted_deputy(): void
    {
        $now = new DateTime();

        /** @var ObjectProphecy|DateTimeProvider $dateTimeProvider */
        $dateTimeProvider = self::prophesize(DateTimeProvider::class);
        $dateTimeProvider->getDateTime()->shouldBeCalled()->willReturn($now);

        $expected = [
            'trigger' => 'ADMIN_BUTTON',
            'deleted_on' => $now->format(DateTime::ATOM),
            'deleted_by' => 'super-admin@email.com',
            'subject_full_name' => 'Roisin Murphy',
            'subject_email' => 'r.murphy@email.com',
            'subject_role' => 'ROLE_LAY_DEPUTY',
            'event' => 'DEPUTY_DELETED',
            'type' => 'audit'
        ];

        $actual = (new AuditEvents($dateTimeProvider->reveal()))->userDeleted(
            'ADMIN_BUTTON',
            'super-admin@email.com',
            'Roisin Murphy',
            'r.murphy@email.com',
            'ROLE_LAY_DEPUTY'
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @dataProvider adminRoleProvider
     */
    public function userDeleted_admin(string $role): void
    {
        $now = new DateTime();

        /** @var ObjectProphecy|DateTimeProvider $dateTimeProvider */
        $dateTimeProvider = self::prophesize(DateTimeProvider::class);
        $dateTimeProvider->getDateTime()->shouldBeCalled()->willReturn($now);

        $expected = [
            'trigger' => 'ADMIN_BUTTON',
            'deleted_on' => $now->format(DateTime::ATOM),
            'deleted_by' => 'super-admin@email.com',
            'subject_full_name' => 'Robyn Konichiwa',
            'subject_email' => 'r.konichiwa@email.com',
            'subject_role' => $role,
            'event' => 'ADMIN_DELETED',
            'type' => 'audit'
        ];

        $actual = (new AuditEvents($dateTimeProvider->reveal()))->userDeleted(
            'ADMIN_BUTTON',
            'super-admin@email.com',
            'Robyn Konichiwa',
            'r.konichiwa@email.com',
            $role
        );

        $this->assertEquals($expected, $actual);
    }

    public function adminRoleProvider()
    {
        return [
            'admin' => [User::ROLE_ADMIN],
            'super admin' => [User::ROLE_SUPER_ADMIN],
        ];
    }
}
