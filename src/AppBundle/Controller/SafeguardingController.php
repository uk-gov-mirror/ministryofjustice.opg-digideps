<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Safeguarding;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * @Route("/safeguarding")
 */
class SafeguardingController extends RestController
{
    /**
     * @Route("/upsert")
     * @Method({"POST", "PUT"})
     */
    public function upsertAction()
    {
        $data = $this->deserializeBodyContent();

        $report = $this->findEntityBy('Report', $data['report_id'], 'Report not found');
        $safeguarding = $report->getSafeguarding();

        $this->updateSafeguardingInfo($data, $safeguarding);

        $this->getEntityManager()->persist($safeguarding);
        $this->getEntityManager()->flush();

        return ['id' => $safeguarding->getId() ];
    }

    /**
     * @Route("/find-by-report-id/{reportId}")
     * @Method({"GET"})
     *
     * @param integer $reportId
     */
    public function findByReportIdAction($reportId)
    {
        $report = $this->findEntityBy('Report', $reportId);
        return $this->getRepository('Safeguarding')->findBy(['report'=>$report]);
    }

    /**
     * @Route("/{id}")
     * @Method({"GET"})
     * @param integer $id
     * @return \AppBundle\Entity\Safeguarding
     */
    public function get($id)
    {
        $request = $this->getRequest();
        $serialiseGroups = $request->query->has('groups')? $request->query->get('groups') : [ 'basic'];
        $this->setJmsSerialiserGroup($serialiseGroups);

        $safeguarding = $this->findEntityBy('Safeguarding', $id, "Safeguarding with id:".$id." not found");

        return $safeguarding;
    }

    /**
     * @Route("/{id}")
     * @Method({"DELETE"})
     */
    public function deleteAction($id)
    {
        $safeguarding = $this->findEntityBy('Safeguarding', $id, 'Safeguarding not found');

        $this->getEntityManager()->remove($safeguarding);
        $this->getEntityManager()->flush();

        return [ ];
    }

    /**
     * @param  array $data
     * @param  \AppBundle\Entity\Safeguarding $safeguarding
     * @return \AppBundle\Entity\Report $report
     */
    private function updateSafeguardingInfo($data,\AppBundle\Entity\Safeguarding $safeguarding)
    {
        if(array_key_exists('do_you_live_with_client', $data)) {
            $safeguarding->setDoYouLiveWithClient($data['do_you_live_with_client']);
        }

        if(array_key_exists('how_often_do_you_visit', $data)) {
            $safeguarding->setHowOftenDoYouVisit($data['how_often_do_you_visit']);
        }

        if(array_key_exists('how_often_do_you_phone_or_video_call', $data)) {
            $safeguarding->setHowOftenDoYouPhoneOrVideoCall($data['how_often_do_you_phone_or_video_call']);
        }

        if(array_key_exists('how_often_do_you_write_email_or_letter', $data)) {
            $safeguarding->setHowOftenDoYouWriteEmailOrLetter($data['how_often_do_you_write_email_or_letter']);
        }

        if(array_key_exists('how_often_does_client_see_other_people', $data)) {
            $safeguarding->setHowOftenDoesClientSeeOtherPeople($data['how_often_does_client_see_other_people']);
        }

        if(array_key_exists('anything_else_to_tell', $data)) {
            $safeguarding->setAnythingElseToTell($data['anything_else_to_tell']);
        }

        if(array_key_exists('does_client_receive_paid_care', $data)) {
            $safeguarding->setDoesClientReceivePaidCare($data['does_client_receive_paid_care']);
        }

        if(array_key_exists('how_is_care_funded', $data)) {
            $safeguarding->setHowIsCareFunded($data['how_is_care_funded']);
        }

        if(array_key_exists('who_is_doing_the_caring', $data)) {
            $safeguarding->setWhoIsDoingTheCaring($data['who_is_doing_the_caring']);
        }

        if(array_key_exists('does_client_have_a_care_plan', $data)) {
            $safeguarding->setDoesClientHaveACarePlan($data['does_client_have_a_care_plan']);
        }

        if(array_key_exists('when_was_care_plan_last_reviewed', $data)) {

            if(!empty($data['when_was_care_plan_last_reviewed'])){
                $safeguarding->setWhenWasCarePlanLastReviewed(new \DateTime($data['when_was_care_plan_last_reviewed']));
            }else{
                $safeguarding->setWhenWasCarePlanLastReviewed(null);
            }
        }

        return $safeguarding;
    }
}