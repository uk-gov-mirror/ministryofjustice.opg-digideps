<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity as EntityDir;

/**
 * @Route("/report")
 */
class DecisionController extends RestController
{
    /**
     * @Route("/{reportId}/decisions")
     * @Method({"GET"})
     *
     * @param integer $reportId
     */
    public function getDecisions($reportId)
    {
        $this->denyAccessUnlessGranted(EntityDir\Role::LAY_DEPUTY);
        
        $report = $this->findEntityBy('Report', $reportId);
        $this->denyAccessIfReportDoesNotBelongToUser($report);
        
        return $this->getRepository('Decision')->findBy(['report' => $report]);
    }
    
    /**
     * @Route("/decision")
     * @Method({"POST", "PUT"})
     */
    public function upsertDecision(Request $request)
    {
        $this->denyAccessUnlessGranted(EntityDir\Role::LAY_DEPUTY);
        
        $data = $this->deserializeBodyContent($request);

        if ($request->getMethod() == "PUT") {
            $this->validateArray($data, [
                'id' => 'mustExist'
            ]);
            $decision = $this->findEntityBy('Decision', $data['id'], "Decision with not found");
            $this->denyAccessIfReportDoesNotBelongToUser($decision->getReport());
        } else {
            $this->validateArray($data, [
                'report_id' => 'mustExist'
            ]);
            $report = $this->findEntityBy('Report', $data['report_id'], 'Report not found');
            $this->denyAccessIfReportDoesNotBelongToUser($report);
            $decision = new EntityDir\Decision();
            $decision->setReport($report);
        }

        $this->validateArray($data, [
            'description' => 'mustExist', 
            'client_involved_boolean' => 'mustExist', 
            'client_involved_details' => 'mustExist', 
        ]);
        
        $this->hydrateEntityWithArrayData($decision, $data, [
            'description' => 'setDescription',
            'client_involved_boolean' => 'setClientInvolvedBoolean',
            'client_involved_details' => 'setClientInvolvedDetails',
        ]);

        $this->persistAndFlush($decision);

        return ['id' => $decision->getId()];
    }


    /**
     * @Route("/decision/{id}")
     * @Method({"GET"})
     * 
     * @param integer $id
     */
    public function getOneById(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(EntityDir\Role::LAY_DEPUTY);
        
        if ($request->query->has('groups')) {
            $this->setJmsSerialiserGroups((array)$request->query->get('groups'));
        }

        $decision = $this->findEntityBy('Decision', $id, "Decision with id:" . $id . " not found");
        $this->denyAccessIfReportDoesNotBelongToUser($decision->getReport());
        
        return $decision;
    }


    /**
     * @Route("/decision/{id}")
     * @Method({"DELETE"})
     */
    public function deleteDecision($id)
    {
        $this->denyAccessUnlessGranted(EntityDir\Role::LAY_DEPUTY);
        
        $decision = $this->findEntityBy('Decision', $id, "Decision with id:" . $id . " not found");
        $this->denyAccessIfReportDoesNotBelongToUser($decision->getReport());
        
        $this->getEntityManager()->remove($decision);
        $this->getEntityManager()->flush($decision);

        return [];
    }

}