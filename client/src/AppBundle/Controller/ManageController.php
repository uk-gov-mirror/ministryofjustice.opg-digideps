<?php

namespace AppBundle\Controller;

use AppBundle\Service\Availability as ServiceAvailability;
use AppBundle\Service\Availability\NotifyAvailability;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/manage")
 */
class ManageController extends AbstractController
{
    /**
     * @var NotifyAvailability
     */
    private $notifyAvailability;

    public function __construct(NotifyAvailability $notifyAvailability)
    {
        $this->notifyAvailability = $notifyAvailability;
    }

    /**
     * @Route("/availability", methods={"GET"})
     */
    public function availabilityAction()
    {
        list($healthy, $services, $errors) = $this->servicesHealth();

        $response = $this->render('AppBundle:Manage:availability.html.twig', [
            'services' => $services,
            'errors' => $errors,
            'environment' => $this->get('kernel')->getEnvironment(),
        ]);

        $response->setStatusCode($healthy ? 200 : 500);

        return $response;
    }

    /**
     * @Route("/availability/pingdom", methods={"GET"})
     */
    public function healthCheckXmlAction()
    {
        list($healthy, $services, $errors, $time) = $this->servicesHealth();

        $response = $this->render('AppBundle:Manage:health-check.xml.twig', [
            'status' => $healthy ? 'OK' : 'ERRORS: ',
            'time' => $time * 1000,
        ]);
        $response->setStatusCode($healthy ? 200 : 500);
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }

    /**
     * @Route("/elb", name="manage-elb", methods={"GET"})
     * @Template("AppBundle:Manage:elb.html.twig")
     */
    public function elbAction()
    {
        return ['status' => 'OK'];
    }

    /**
     * @return array [true if healthy, services array, string with errors, time in secs]
     */
    private function servicesHealth()
    {
        $start = microtime(true);

        $services = [
            new ServiceAvailability\RedisAvailability($this->container),
            new ServiceAvailability\ApiAvailability($this->container),
            new ServiceAvailability\SiriusApiAvailability($this->container),
            $this->notifyAvailability
        ];

        if ($this->getParameter('env') !== 'admin') {
            $services[] = new ServiceAvailability\WkHtmlToPdfAvailability($this->container);
            $services[] = new ServiceAvailability\ClamAvAvailability($this->container);
        }

        $healthy = true;
        $errors = [];

        foreach ($services as $service) {
            if (!$service->isHealthy()) {
                $healthy = false;
                $errors[] = $service->getErrors();
            }
        }

        return [$healthy, $services, $errors, microtime(true) - $start];
    }
}
