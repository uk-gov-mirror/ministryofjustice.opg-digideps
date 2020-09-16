<?php declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity as EntityDir;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("")
 */
class ClientContactController extends RestController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/clients/{clientId}/clientcontacts", name="clientcontact_add", methods={"POST"})
     * @Security("has_role('ROLE_ORG')")
     */
    public function add(Request $request, $clientId)
    {
        $client = $this->findEntityBy(EntityDir\Client::class, $clientId);
        $this->denyAccessIfClientDoesNotBelongToUser($client);

        $data = $this->deserializeBodyContent($request);
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
        $this->persistAndFlush($clientContact);

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

        $data = $this->deserializeBodyContent($request);
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
        $this->getEntityManager()->flush($clientContact);
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
        $this->setJmsSerialiserGroups($serialisedGroups);

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
    public function delete($id)
    {
        try {
            $clientContact = $this->findEntityBy(EntityDir\ClientContact::class, $id);
            $this->denyAccessIfClientDoesNotBelongToUser($clientContact->getClient());

            $this->getEntityManager()->remove($clientContact);
            $this->getEntityManager()->flush($clientContact);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete client contact ID: ' . $id . ' - ' . $e->getMessage());
        }

        return [];
    }
}
