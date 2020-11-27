<?php declare(strict_types=1);

namespace AppBundle\Service\Audit;

use AppBundle\Entity\User;
use AppBundle\Service\Time\DateTimeProvider;
use DateTime;

final class AuditEvents
{
    const EVENT_USER_EMAIL_CHANGED = 'USER_EMAIL_CHANGED';
    const EVENT_ROLE_CHANGED = 'ROLE_CHANGED';
    const EVENT_CLIENT_EMAIL_CHANGED = 'CLIENT_EMAIL_CHANGED';
    const EVENT_CLIENT_DELETED = 'CLIENT_DELETED';
    const EVENT_DEPUTY_DELETED = 'DEPUTY_DELETED';
    const EVENT_ADMIN_DELETED = 'ADMIN_DELETED';

    const TRIGGER_ADMIN_USER_EDIT = 'ADMIN_USER_EDIT';
    const TRIGGER_ADMIN_BUTTON = 'ADMIN_BUTTON';
    const TRIGGER_CSV_UPLOAD = 'CSV_UPLOAD';
    const TRIGGER_DEPUTY_USER_EDIT_SELF = 'DEPUTY_USER_EDIT_SELF';
    const TRIGGER_DEPUTY_USER_EDIT = 'DEPUTY_USER_EDIT';
    const TRIGGER_CODEPUTY_CREATED = 'CODEPUTY_CREATED';

    /**
     * @var DateTimeProvider
     */
    private $dateTimeProvider;

    public function __construct(DateTimeProvider $dateTimeProvider)
    {
        $this->dateTimeProvider = $dateTimeProvider;
    }

    /**
     * @param string $trigger
     * @param string $caseNumber
     * @param string $dischargedBy
     * @param string $deputyName
     * @param DateTime|null $deputyshipStartDate
     * @return array
     * @throws \Exception
     */
    public function clientDischarged(
        string $trigger,
        string $caseNumber,
        string $dischargedBy,
        string $deputyName,
        ?DateTime $deputyshipStartDate
    ): array {
        $event = [
            'trigger' => $trigger,
            'case_number' => $caseNumber,
            'discharged_by' => $dischargedBy,
            'deputy_name' => $deputyName,
            'discharged_on' => $this->dateTimeProvider->getDateTime()->format(DateTime::ATOM),
            'deputyship_start_date' => $deputyshipStartDate ? $deputyshipStartDate->format(DateTime::ATOM) : null,
        ];

        return $event + $this->baseEvent(AuditEvents::EVENT_CLIENT_DELETED);
    }

    public function userEmailChanged(
        string $trigger,
        string $emailChangedFrom,
        string $emailChangedTo,
        string $changedBy,
        string $subjectFullName,
        string $subjectRole
    ) {
        $event = [
            'trigger' => $trigger,
            'email_changed_from' => $emailChangedFrom,
            'email_changed_to' => $emailChangedTo,
            'changed_on' => $this->dateTimeProvider->getDateTime()->format(DateTime::ATOM),
            'changed_by' => $changedBy,
            'subject_full_name' => $subjectFullName,
            'subject_role' => $subjectRole,
        ];

        return $event + $this->baseEvent(AuditEvents::EVENT_USER_EMAIL_CHANGED);
    }

    public function clientEmailChanged(
        string $trigger,
        ?string $emailChangedFrom,
        ?string $emailChangedTo,
        string $changedByEmail,
        string $subjectFullName
    ) {
        $event = [
            'trigger' => $trigger,
            'email_changed_from' => $emailChangedFrom,
            'email_changed_to' => $emailChangedTo,
            'changed_on' => $this->dateTimeProvider->getDateTime()->format(DateTime::ATOM),
            'changed_by' => $changedByEmail,
            'subject_full_name' => $subjectFullName,
            'subject_role' => 'CLIENT',
        ];

        return $event + $this->baseEvent(AuditEvents::EVENT_CLIENT_EMAIL_CHANGED);
    }

    /**
     * @param string $eventName
     * @return array
     */
    private function baseEvent(string $eventName): array
    {
        return [
            'event' => $eventName,
            'type' => 'audit'
        ];
    }

    /**
     * @param string $trigger
     * @param string $changedFrom
     * @param string $changedTo
     * @param string $changedByEmail
     * @param string $userChangedEmail
     * @return array
     * @throws \Exception
     */
    public function roleChanged(string $trigger, string $changedFrom, string $changedTo, string $changedByEmail, string $userChangedEmail): array
    {
        $event = [
            'trigger' => $trigger,
            'role_changed_from' => $changedFrom,
            'role_changed_to' => $changedTo,
            'changed_by' => $changedByEmail,
            'changed_on' => $this->dateTimeProvider->getDateTime()->format(DateTime::ATOM),
            'user_changed' => $userChangedEmail,
        ];

        return $event + $this->baseEvent(AuditEvents::EVENT_ROLE_CHANGED);
    }

    /**
     * @param string $trigger
     * @param string $deletedBy
     * @param string $subjectFullName
     * @param string $subjectEmail
     * @param string $subjectRole
     * @return array|string[]
     * @throws \Exception
     */
    public function userDeleted(string $trigger, string $deletedBy, string $subjectFullName, string $subjectEmail, string $subjectRole): array
    {
        $event = [
            'trigger' => $trigger,
            'deleted_on' => $this->dateTimeProvider->getDateTime()->format(DateTime::ATOM),
            'deleted_by' => $deletedBy,
            'subject_full_name' => $subjectFullName,
            'subject_email' => $subjectEmail,
            'subject_role' => $subjectRole,
        ];

        $eventType = in_array($subjectRole, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN]) ?
            AuditEvents::EVENT_ADMIN_DELETED : AuditEvents::EVENT_DEPUTY_DELETED;

        return $event + $this->baseEvent($eventType);
    }
}
