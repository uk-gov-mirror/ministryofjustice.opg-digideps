<?php

namespace AppBundle\Controller\Report;

use AppBundle\Controller\RestController;
use AppBundle\Entity as EntityDir;
use AppBundle\Service\Formatter\RestFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/report")
 */
class LifestyleController extends RestController
{
    private EntityManagerInterface $em;
    private RestFormatter $formatter;

    private array $sectionIds = [EntityDir\Report\Report::SECTION_LIFESTYLE];

    public function __construct(EntityManagerInterface $em, RestFormatter $formatter)
    {
        $this->em = $em;
        $this->formatter = $formatter;
    }

    /**
     * @Route("/lifestyle", methods={"POST"})
     * @Security("has_role('ROLE_DEPUTY')")
     */
    public function addAction(Request $request)
    {
        $lifestyle = new EntityDir\Report\Lifestyle();
        $data = $this->formatter->deserializeBodyContent($request);

        $report = $this->findEntityBy(EntityDir\Report\Report::class, $data['report_id']);
        $this->denyAccessIfReportDoesNotBelongToUser($report);

        $lifestyle->setReport($report);
        $this->updateInfo($data, $lifestyle);

        $this->em->persist($lifestyle);
        $this->em->flush();

        $report->updateSectionsStatusCache($this->sectionIds);
        $this->em->flush();

        return ['id' => $lifestyle->getId()];
    }

    /**
     * @Route("/lifestyle/{id}", methods={"PUT"})
     * @Security("has_role('ROLE_DEPUTY')")
     */
    public function updateAction(Request $request, $id)
    {
        $lifestyle = $this->findEntityBy(EntityDir\Report\Lifestyle::class, $id);
        $report = $lifestyle->getReport();
        $this->denyAccessIfReportDoesNotBelongToUser($lifestyle->getReport());

        $data = $this->formatter->deserializeBodyContent($request);
        $this->updateInfo($data, $lifestyle);
        $this->em->flush();

        $report->updateSectionsStatusCache($this->sectionIds);
        $this->em->flush();

        return ['id' => $lifestyle->getId()];
    }

    /**
     * @Route("/{reportId}/lifestyle", methods={"GET"})
     * @Security("has_role('ROLE_DEPUTY')")
     *
     * @param int $reportId
     */
    public function findByReportIdAction($reportId)
    {
        $report = $this->findEntityBy(EntityDir\Report\Report::class, $reportId);
        $this->denyAccessIfReportDoesNotBelongToUser($report);

        $ret = $this->getRepository(EntityDir\Report\Lifestyle::class)->findByReport($report);

        return $ret;
    }

    /**
     * @Route("/lifestyle/{id}", methods={"GET"})
     * @Security("has_role('ROLE_DEPUTY')")
     *
     * @param int $id
     */
    public function getOneById(Request $request, $id)
    {
        $serialiseGroups = $request->query->has('groups')
            ? (array) $request->query->get('groups') : ['lifestyle'];
        $this->formatter->setJmsSerialiserGroups($serialiseGroups);

        $lifestyle = $this->findEntityBy(EntityDir\Report\Lifestyle::class, $id, 'Lifestyle with id:' . $id . ' not found');
        $this->denyAccessIfReportDoesNotBelongToUser($lifestyle->getReport());

        return $lifestyle;
    }

    /**
     * @Route("/lifestyle/{id}", methods={"DELETE"})
     * @Security("has_role('ROLE_DEPUTY')")
     */
    public function deleteLifestyle($id)
    {
        $lifestyle = $this->findEntityBy(EntityDir\Report\Lifestyle::class, $id, 'VisitsCare not found');
        $report = $lifestyle->getReport();

        $this->denyAccessIfReportDoesNotBelongToUser($lifestyle->getReport());

        $this->em->remove($lifestyle);
        $this->em->flush();

        $report->updateSectionsStatusCache($this->sectionIds);
        $this->em->flush();

        return [];
    }

    /**
     * @param array                      $data
     * @param EntityDir\Report\Lifestyle $lifestyle
     *
     * @return \AppBundle\Entity\Report\Report $report
     */
    private function updateInfo(array $data, EntityDir\Report\Lifestyle $lifestyle)
    {
        if (array_key_exists('care_appointments', $data)) {
            $lifestyle->setCareAppointments($data['care_appointments']);
        }

        if (array_key_exists('does_client_undertake_social_activities', $data)) {
            $yesNo = $data['does_client_undertake_social_activities'];
            $lifestyle->setDoesClientUndertakeSocialActivities($yesNo);
            $lifestyle->setActivityDetailsYes($yesNo === 'yes' ?  $data['activity_details_yes'] : null);
            $lifestyle->setActivityDetailsNo($yesNo === 'no' ?  $data['activity_details_no'] : null);
        }

        return $lifestyle;
    }
}
