<?php
namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Act on session on each request
 * 
 */
class SessionListener
{
    /**
     * @var integer
     */
    private $idleTimeout;  

    /**
     * @param array $options keys: idleTimeout (seconds)
     * @throws \InvalidArgumentException
     */
    public function __construct(array $options)
    {
        $this->idleTimeout = (int)$options['idleTimeout'];
        if ($this->idleTimeout < 30) {
            throw new \InvalidArgumentException(__CLASS__ . " :session timeout cannot be lower than 30 seconds");
        }
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // Only operate on the master request and when there is a session
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()
            || !$event->getRequest()->hasSession()) {
            return;
        }
        
        $session = $event->getRequest()->getSession();
        
        $lastUsed = (int)$session->getMetadataBag()->getLastUsed();
        if (!$lastUsed) {
            return;
        }
        
        $idleTime = time() - $lastUsed;
        $hasReachedIdleTimeout = $idleTime > $this->idleTimeout;
        
        if ($hasReachedIdleTimeout) {
            //Invalidate the current session and throw an exception
            $session->invalidate();
            throw new CredentialsExpiredException();
        }
    }
}