<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class RestInputOuputFormatter
{
    /**
     * @var Serializer
     */
    private $serializer;
    
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $defaultFormat;
    
    /**
     * @var array
     */
    private $supportedFormats;
    
    /**
     * @var boolean
     */
    private $debug;

    /**
     * @var Closure
     */
    private $responseModifiers = [];
    
    /**
     * @var Closure
     */
    private $contextModifiers = [];
    
    public function __construct(Serializer $serializer, LoggerInterface $logger, array $supportedFormats, $defaultFormat, $debug)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->supportedFormats = array_values($supportedFormats);
        $this->defaultFormat = $defaultFormat;
        $this->debug = $debug;
    }

    /**
     * @param Request $request
     * 
     * @return array
     */
    public function requestContentToArray(Request $request)
    {
        $format = $request->getContentType();

        $content = $request->getContent();
        if (!$content) {
            return [];
        }
        
        return $this->serializer->deserialize($request->getContent(), 'array', $format);
    }
    
    
    /**
     * @param array $data for custom serialise groups, use serialise_groups
     * @param Request $request
     * @return Response
     */
    private function arrayToResponse($data, Request $request)
    {
        $format = $request->getContentType();

        if (!in_array($format, $this->supportedFormats)) {
            if ($this->defaultFormat) {
                $format = $this->defaultFormat;
            } else {
                throw new \Exception("format $format not supported and  defaultFormat not defined. Supported formats: " . implode(',', $this->supportedFormats));
            }
        }

        $context = SerializationContext::create(); //->setSerializeNull(true);
        // context modifier
        foreach ($this->contextModifiers as $modifier) {
            $modifier($context);
        }
        
        $serializedData = $this->serializer->serialize($data, $format, $context);
        $response = new Response($serializedData);
        $response->headers->set('Content-Type', 'application/' . $format);
        // response modifier
        foreach ($this->responseModifiers as $modifier) {
            $modifier($response);
        }
        
        return $response;
    }

    /**
     * Attach the following with
     * services:
            kernel.listener.responseConverter:
                class: AppBundle\EventListener\RestInputOuputFormatter
                arguments: [ @serializer, ["json", "xml"] ]
                tags:
                    - { name: kernel.event_listener, event: kernel.view, method: onKernelView }            
     */
    
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $data = array(
            'success' => true, 
            'data' => $event->getControllerResult(), 
            'message' => ''
        );

        $response = $this->arrayToResponse($data, $event->getRequest());
        
        $event->setResponse($response);
    }
    
    /**
     * Attach the following with
       services:
            kernel.listener.responseConverter:
                class: AppBundle\EventListener\RestInputOuputFormatter
                arguments: [ @serializer, ["json", "xml"] ]
                tags:
                    - { name: kernel.event_listener, event: kernel.exception, method: onKernelException }
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();
        $message = $e->getMessage();
        $code = $e->getCode();
        
        // log exception
        $this->logger->warning($message);
        
        // transform message and code 
        if ($code < 400 || $code > 599) {
            $code = 500;
        }
        if ($e instanceof \InvalidArgumentException) {
            $code = 400;
        }
        
        $data = array(
            'success' => false, 
            'data' => '', 
            'message' => $message,
            'stacktrace' => ($this->debug) ? 
                    sprintf('%s: %s', get_class($e), substr($e->getTraceAsString(), 0, 1000))
                    : 'enable debug mode to see it',
            'code' => $code
        );
        
        $response = $this->arrayToResponse($data, $event->getRequest());
        $response->setStatusCode($code);
        
        $event->setResponse($response);
    }
    
    
    public static function onKernelRequest(GetResponseEvent $event)
    {
        function_exists('xdebug_disable') && xdebug_disable();

        register_shutdown_function(function () use ($event) {
            $lastError = error_get_last();
            if (!$lastError) {
                return;
            }
            echo json_encode(array(
                'success' => false, 
                'data' => '', 
                'message' => "{$lastError['message']} {$lastError['file']}:{$lastError['line']} "
            ));
            die;
            //TODO find a way to use kernely temrinate instead, usgin 
        });

    }
    
    /**
     * @param \Closure $f
     */
    public function addResponseModifier(\Closure $f)
    {
        $this->responseModifiers[] = $f;
    }
    
     /**
     * @param \Closure $f
     */
    public function addContextModifier(\Closure $f)
    {
        $this->contextModifiers[] = $f;
    }
    
}