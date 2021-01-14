<?php

namespace App\Controller;

use App\Entity as EntityDir;
use App\Service\Formatter\RestFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("")
 */
class ClientContactController extends RestController
{
    private EntityManagerInterface $em;
    private RestFormatter $formatter;

    public function __construct(EntityManagerInterface $em, RestFormatter $formatter)
    {
        $this->em = $em;
        $this->formatter = $formatter;
    }

    /**
     * @Route("/clients/{clientId}/clientcontacts", name="clientcontact_add", methods={"POST"})
     * @Security("has_role('ROLE_ORG')")
     */
    public function add(Request $request, $clientId)
    {
        $client = $this->findEntityBy(EntityDir\Client::class, $clientId);
        $this->denyAccessIfClientDoesNotBelongToUser($client);

        $data = $this->formatter->deserializeBodyContent($request);
        $clientContact = new EntityDir\ClientContact();
        $this->hydrateEntityWithArrayData($clientContact, $data, [
            'first_name'   => 'setFirstName',
            'last_name'    => 'setLastName',
            'job_title'    => 'setJobTitle',
            'phone'        => 'setPhone',
            'address1'     => 'setAddress1',
            'address2'     => 'setAddress2',
            'address3'     => 'setAddress3',
            'address_postcode' => 'setAddressPostcode',
            'address_country'  => 'setAddressCountry',
            'email'        => 'setEmail',
            'org_name'     => 'setOrgName',
        ]);

        $clientContact->setClient($client);
        $clientContact->setCreatedBy($this->getUser());

        $this->em->persist($clientContact);
        $this->em->flush();

        return ['id' => $clientContact->getId()];
    }

    /**
     * Update contact
     * Only the creator can update the note
     *
     * @Route("/clientcontacts/{id}", methods={"PUT"})
     * @Security("has_role('ROLE_ORG')")
     */
    public function update(Request $request, $id)
    {
        $clientContact = $this->findEntityBy(EntityDir\ClientContact::class, $id);
        $this->denyAccessIfClientDoesNotBelongToUser($clientContact->getClient());

        $data = $this->formatter->deserializeBodyContent($request);
        $this->hydrateEntityWithArrayData($clientContact, $data, [
            'first_name'   => 'setFirstName',
            'last_name'    => 'setLastName',
            'job_title'    => 'setJobTitle',
            'phone'        => 'setPhone',
            'address1'     => 'setAddress1',
            'address2'     => 'setAddress2',
            'address3'     => 'setAddress3',
            'address_postcode' => 'setAddressPostcode',
            'address_country'  => 'setAddressCountry',
            'email'        => 'setEmail',
            'org_name'     => 'setOrgName',
        ]);
        $this->em->flush($clientContact);
        return $clientContact->getId();
    }

    /**
     * @Route("/clientcontacts/{id}", methods={"GET"})
     * @Security("has_role('ROLE_ORG')")
     */
    public function getOneById(Request $request, $id)
    {
        $serialisedGroups = $request->query->has('groups')
            ? (array) $request->query->get('groups')
            : ['clientcontact', 'clientcontact-client', 'client', 'client-users', 'current-report', 'report-id', 'user'];
        $this->formatter->setJmsSerialiserGroups($serialisedGroups);

        $clientContact = $this->findEntityBy(EntityDir\ClientContact::class, $id);
        $this->denyAccessIfClientDoesNotBelongToUser($clientContact->getClient());

        return $clientContact;
    }

    /**
     * Delete contact
     * Only the creator can delete the note
     *
     * @Route("/clientcontacts/{id}", methods={"DELETE"})
     * @Security("has_role('ROLE_ORG')")
     */
    public function delete($id, LoggerInterface $logger)
    {
        try {
            $clientContact = $this->findEntityBy(EntityDir\ClientContact::class, $id);
            $this->denyAccessIfClientDoesNotBelongToUser($clientContact->getClient());

            $this->em->remove($clientContact);
            $this->em->flush($clientContact);
        } catch (\Throwable $e) {
            $logger->error('Failed to delete client contact ID: ' . $id . ' - ' . $e->getMessage());
        }

        return [];
    }
}