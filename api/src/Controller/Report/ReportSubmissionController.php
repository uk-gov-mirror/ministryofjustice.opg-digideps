<?php

namespace App\Controller\Report;

use App\Controller\RestController;
use App\Entity as EntityDir;
use App\Entity\Report\Document;
use App\Entity\Report\ReportSubmission;
use App\Service\Auth\AuthService;
use App\Service\Formatter\RestFormatter;
use App\Transformer\ReportSubmission\ReportSubmissionSummaryTransformer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/report-submission")
 */
class ReportSubmissionController extends RestController
{
    private EntityManagerInterface $em;
    private AuthService $authService;
    private RestFormatter $formatter;

    const QUEUEABLE_STATUSES = [
        null,
        Document::SYNC_STATUS_TEMPORARY_ERROR,
        Document::SYNC_STATUS_PERMANENT_ERROR
    ];

    private static array $jmsGroups = [
        'report-submission',
        'report-type',
        'report-client',
        'ndr-client',
        'ndr',
        'report-period',
        'client-name',
        'client-case-number',
        'client-email',
        'client-discharged',
        'user-name',
        'user-rolename',
        'user-teamname',
        'documents',
        'synchronisation',
    ];


    public function __construct(EntityManagerInterface $em, AuthService $authService, RestFormatter $formatter)
    {
        $this->em = $em;
        $this->authService = $authService;
        $this->formatter = $formatter;
    }

    /**
     * @Route("", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function getAll(Request $request)
    {
        $repo = $this->getRepository(EntityDir\Report\ReportSubmission::class); /* @var $repo EntityDir\Repository\ReportSubmissionRepository */

        $ret = $repo->findByFiltersWithCounts(
            $request->get('status'),
            $request->get('q'),
            $request->get('created_by_role'),
            $request->get('offset', 0),
            $request->get('limit', 15),
            $request->get('orderBy', 'createdOn'),
            $request->get('order', 'ASC')
        );

        $this->formatter->setJmsSerialiserGroups(self::$jmsGroups);

        return $ret;
    }

    /**
     * @Route("/{id}", requirements={"id":"\d+"}, methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function getOneById(Request $request, $id)
    {
        $ret = $this->getRepository(EntityDir\Report\ReportSubmission::class)->findOneByIdUnfiltered($id);

        $this->formatter->setJmsSerialiserGroups(array_merge(self::$jmsGroups, ['document-storage-reference']));

        return $ret;
    }

    /**
     * Update documents
     * return array of storage references, for admin area to delete if needed
     *
     * @Route("/{reportSubmissionId}", requirements={"reportSubmissionId":"\d+"}, methods={"PUT"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function update(Request $request, $reportSubmissionId)
    {
        /* @var $reportSubmission EntityDir\Report\ReportSubmission */
        $reportSubmission = $this->findEntityBy(EntityDir\Report\ReportSubmission::class, $reportSubmissionId);

        $data = $this->formatter->deserializeBodyContent($request);

        if (!empty($data['archive'])) {
            $reportSubmission->setArchived(true);
            $reportSubmission->setArchivedBy($this->getUser());
        }

        $this->em->flush();

        return $reportSubmission->getId();
    }

    /**
     * Separating this from update() as it needs to be accessible via client secret which removes the
     * User from the request.
     *
     * @Route("/{reportSubmissionId}/update-uuid", requirements={"reportSubmissionId":"\d+"}, methods={"PUT"})
     */
    public function updateUuid(Request $request, $reportSubmissionId)
    {
        if (!$this->authService->isSecretValid($request)) {
            throw new UnauthorisedException('client secret not accepted.');
        }

        /* @var $reportSubmission EntityDir\Report\ReportSubmission */
        $reportSubmission = $this->findEntityBy(EntityDir\Report\ReportSubmission::class, $reportSubmissionId);

        $data = $this->formatter->deserializeBodyContent($request);

        if (!empty($data['uuid'])) {
            $reportSubmission->setUuid($data['uuid']);
        }

        $this->em->flush();

        return $reportSubmission->getId();
    }

    /**
     * Get old report submissions.
     * Called from ADMIN cron
     *
     * @Route("/old", methods={"GET"})
     */
    public function getOld(Request $request)
    {
        if (!$this->authService->isSecretValidForRole(EntityDir\User::ROLE_ADMIN, $request)) {
            throw new \RuntimeException(__METHOD__ . ' only accessible from ADMIN container.', 403);
        }

        $repo = $this->getRepository(EntityDir\Report\ReportSubmission::class); /* @var $repo EntityDir\Repository\ReportSubmissionRepository */

        $ret = $repo->findDownloadableOlderThan(new \DateTime(EntityDir\Report\ReportSubmission::REMOVE_FILES_WHEN_OLDER_THAN), 100);

        $this->formatter->setJmsSerialiserGroups(['report-submission-id', 'report-submission-documents', 'document-storage-reference']);

        return $ret;
    }

    /**
     * Set report undownloadable (and remove the storage reference for the files.
     * Called from ADMIN cron
     *
     * @Route("/{id}/set-undownloadable", requirements={"id":"\d+"}, methods={"PUT"})
     */
    public function setUndownloadable($id, Request $request)
    {
        if (!$this->authService->isSecretValidForRole(EntityDir\User::ROLE_ADMIN, $request)) {
            throw new \RuntimeException(__METHOD__ . ' only accessible from ADMIN container.', 403);
        }

        /* @var $reportSubmission EntityDir\Report\ReportSubmission */
        $reportSubmission = $this->getRepository(EntityDir\Report\ReportSubmission::class)->find($id);
        $reportSubmission->setDownloadable(false);
        foreach ($reportSubmission->getDocuments() as $document) {
            $document->setStorageReference(null);
        }

        $this->em->flush();

        return true;
    }

    /**
     * Queue submission documents which have been synced yet
     *
     * @Route("/{id}/queue-documents", requirements={"id":"\d+"}, methods={"PUT"})
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     */
    public function queueDocuments($id)
    {
        /** @var ReportSubmission $reportSubmission */
        $reportSubmission = $this->getRepository(ReportSubmission::class)->find($id);

        if ($reportSubmission->getArchived()) {
            throw new InvalidArgumentException('Cannot queue documents for an archived report submission');
        }

        foreach ($reportSubmission->getDocuments() as $document) {
            if (in_array($document->getSynchronisationStatus(), self::QUEUEABLE_STATUSES)) {
                $document->setSynchronisationStatus(Document::SYNC_STATUS_QUEUED);
                $document->setSynchronisationError(null);
                $document->setSynchronisedBy($this->getUser());
            }
        }

        $this->em->flush();

        return true;
    }

    /**
     * @Route("/casrec_data", name="casrec_data", methods={"GET"})
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param ReportSubmissionSummaryTransformer $reportSubmissionSummaryTransformer
     *
     * @return array
     * @throws \Exception
     */
    public function getCasrecData(Request $request, ReportSubmissionSummaryTransformer $reportSubmissionSummaryTransformer): array
    {
        /* @var $repo EntityDir\Repository\ReportSubmissionRepository */
        $repo = $this->getRepository(EntityDir\Report\ReportSubmission::class);

        $fromDate = $request->get('fromDate') ? new DateTime($request->get('fromDate')) : null;
        $toDate = $request->get('toDate') ? new DateTime($request->get('toDate')) : null;

        $fromDateTime = $fromDate ? $fromDate->setTime(0, 0) : null;
        $toDateTime = $toDate ? $toDate->setTime(23, 59, 59) : null;

        $ret = $repo->findAllReportSubmissions(
            $fromDateTime,
            $toDateTime,
            $request->get('orderBy', 'createdOn'),
            $request->get('order', 'ASC')
        );

        return $reportSubmissionSummaryTransformer->transform($ret);
    }
}
