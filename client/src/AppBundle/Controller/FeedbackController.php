<?php

namespace AppBundle\Controller;

use AppBundle\Form\FeedbackType;
use AppBundle\Service\Client\RestClient;
use AppBundle\Service\Mailer\MailFactory;
use AppBundle\Service\Mailer\MailSender;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class FeedbackController
{
    /**
     * @var MailFactory
     */
    private $mailFactory;

    /**
     * @var MailSender
     */
    private $mailSender;

    /**
     * @var RestClient
     */
    private $restClient;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var FormFactory
     */
    private $form;

    public function __construct(
        MailFactory $mailFactory,
        MailSender $mailSender,
        RestClient $restClient,
        RouterInterface $router,
        Translator $translator,
        FormFactory $form
    ) {
        $this->mailFactory = $mailFactory;
        $this->mailSender = $mailSender;
        $this->restClient = $restClient;
        $this->router = $router;
        $this->translator = $translator;
        $this->form = $form;
    }

    /**
     * @Route("/feedback", name="feedback")
     * @Template("AppBundle:Feedback:index.html.twig")
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function indexAction(Request $request)
    {
        $form = $this->form->create(FeedbackType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Store in database
            $score = $form->get('satisfactionLevel')->getData();
            $comments = $form->get('comments')->getData();

            if ($score) {
                $this->restClient->post('satisfaction/public', [
                    'score' => $score,
                    'comments' => $comments,
                ]);
            }

            // Send notification email
            $feedbackEmail = $this->mailFactory->createGeneralFeedbackEmail($form->getData());
            $this->mailSender->send($feedbackEmail);

            $confirmation = $this->translator->trans('collectionPage.confirmation', [], 'feedback');
            $request->getSession()->getFlashBag()->add('notice', $confirmation);

            return new RedirectResponse($this->router->generate('feedback'));
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
