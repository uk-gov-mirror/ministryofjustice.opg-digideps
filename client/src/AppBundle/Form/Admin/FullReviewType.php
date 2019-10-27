<?php

namespace AppBundle\Form\Admin;

use AppBundle\Entity\Report\Checklist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as FormTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FullReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fullReview', FullReviewChecklistType::class)
            ->add('fullReviewDecision', FormTypes\ChoiceType::class, [
                'choices' => [
                    'checklistPage.form.finalDecision.fullReviewOptions.satisfied' => 'satisfied',
                    'checklistPage.form.finalDecision.fullReviewOptions.furtherCaseworkRequired' => 'further-casework-required',
                    'checklistPage.form.finalDecision.fullReviewOptions.escalate' => 'escalate',
                ],
                'expanded' => true,
            ])
            ->add('save', FormTypes\SubmitType::class)
            ->add('submit', FormTypes\SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Checklist::class,
            'translation_domain' => 'admin-checklist',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'full-review';
    }
}