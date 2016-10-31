<?php

namespace AppBundle\Form\Report;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Translation\TranslatorInterface;

class VisitsCareType extends AbstractType
{
    /**
     * @var int
     */
    private $step;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $clientFirstName;

    /**
     * @param $step
     */
    public function __construct($step, TranslatorInterface $translator, $clientFirstName)
    {
        $this->step = (int)$step;
        $this->translator = $translator;
        $this->clientFirstName = $clientFirstName;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->step === 1) {
            $builder->add('doYouLiveWithClient', 'choice', array(
                'choices' => ['yes' => 'Yes', 'no' => 'No'],
                'expanded' => true,
            ));
            $builder->add('howOftenDoYouContactClient', 'textarea');
        }

        if ($this->step === 2) {
            $builder->add('doesClientReceivePaidCare', 'choice', array(
                'choices' => ['yes' => 'Yes', 'no' => 'No'],
                'expanded' => true,
            ));

            $builder->add('howIsCareFunded', 'choice', array(
                'choices' => [
                    'client_pays_for_all' => $this->translate('form.howIsCareFunded.choices.client_pays_for_all'),
                    'client_gets_financial_help' => $this->translate('form.howIsCareFunded.choices.client_gets_financial_help'),
                    'all_care_is_paid_by_someone_else' => $this->translate('form.howIsCareFunded.choices.all_care_is_paid_by_someone_else'),
                ],
                'expanded' => true,
            ));
        }


        if ($this->step === 3) {
            $builder->add('whoIsDoingTheCaring', 'textarea');
        }

        if ($this->step === 4) {
            $builder->add('doesClientHaveACarePlan', 'choice', array(
                'choices' => ['yes' => 'Yes', 'no' => 'No'],
                'expanded' => true,
            ));

            $builder->add('whenWasCarePlanLastReviewed', 'date', ['widget' => 'text',
                'input' => 'datetime',
                'format' => 'dd-MM-yyyy',
                'invalid_message' => 'visitsCare.whenWasCarePlanLastReviewed.invalidMessage',
            ]);
        }
        $builder->add('save', 'submit');


        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {

            $data = $event->getData();

            if ($this->step == 4) {
                // Strip out the date field if it's not needed. Having a partial date field breaks things
                // if the care plan date is hidden as it receives a date that only has a day
                if (isset($data['doesClientHaveACarePlan']) && $data['doesClientHaveACarePlan'] == 'no') {
                    $data['whenWasCarePlanLastReviewed'] = null;
                }

                // whenWasCarePlanLastReviewed: set day=01 if month and year are set
                if (!empty($data['whenWasCarePlanLastReviewed']['month']) && !empty($data['whenWasCarePlanLastReviewed']['year'])) {
                    $data['whenWasCarePlanLastReviewed']['day'] = '01';
                    $event->setData($data);
                }
            }

            $event->setData($data);
        });
    }

    private function translate($key)
    {
        return $this->translator->trans($key, ['%client%'=>$this->clientFirstName], 'report-visits-care');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'report-visits-care',
            'validation_groups' => function (FormInterface $form) {

                $data = $form->getData();
                $validationGroups = ['visits-care-step' . $this->step];

                if ($this->step == 1 && $data->getDoYouLiveWithClient() == 'no') {
                    $validationGroups[] = 'visits-care-live-client-no';
                }

                if ($this->step == 2 && $data->getDoesClientReceivePaidCare() == 'yes') {
                    $validationGroups[] = 'visits-care-paidCare';
                }

                if ($this->step == 4 && $data->getDoesClientHaveACarePlan() == 'yes') {
                    $validationGroups[] = 'visits-care-hasCarePlan';
                }

                return $validationGroups;
            },
        ]);
    }

    public function getName()
    {
        return 'visits_care';
    }
}
