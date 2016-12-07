<?php

namespace AppBundle\Form\Report\Asset;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class AssetTypeProperty extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('address', 'text')
            ->add('address2', 'text')
            ->add('postcode', 'text')
            ->add('county', 'text');

        $builder
            ->add('occupants', 'textarea')
            ->add('owned', 'choice', array(
                'choices' => ['fully' => 'Fully owned', 'partly' => 'Part-owned'],
                'expanded' => true,
            ))
            ->add('ownedPercentage', 'number', [
                'grouping' => false,
                'precision' => 0,
                'max_length' => 2,
                'pattern' => '[0-9]',
                'invalid_message' => 'asset.property.ownedPercentage.type',
            ])
            ->add('isSubjectToEquityRelease', 'choice', [
                'choices' => ['yes' => 'Yes', 'no' => 'No'],
                'expanded' => true,
            ])
            ->add('value', 'number', [
                'grouping' => true,
                'precision' => 2,
                'invalid_message' => 'asset.property.value.type',
            ])
            ->add('hasMortgage', 'choice', [
                'choices' => ['yes' => 'Yes', 'no' => 'No'],
                'expanded' => true,
            ])
            ->add('mortgageOutstandingAmount', 'number', [
                'grouping' => true,
                'precision' => 2,
                'invalid_message' => 'asset.property.mortgageOutstandingAmount.type',
            ])
            ->add('hasCharges', 'choice', [
                'choices' => ['yes' => 'Yes', 'no' => 'No'],
                'expanded' => true,
            ])
            ->add('isRentedOut', 'choice', [
                'choices' => ['yes' => 'Yes', 'no' => 'No'],
                'expanded' => true,
            ])
            ->add('rentAgreementEndDate', 'date', [
                    'widget' => 'text',
                    'input' => 'datetime',
                    'format' => 'dd-MM-yyyy',
                    'invalid_message' => 'Enter a valid date',
            ])
            ->add('rentIncomeMonth', 'number', [
                'grouping' => true,
                'precision' => 2,
                'invalid_message' => 'asset.property.rentIncomeMonth.type',
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();

                // rentAgreementEndDate; set day=01 if month and year are set
                if (!empty($data['rentAgreementEndDate']['month']) && !empty($data['rentAgreementEndDate']['year'])) {
                    $data['rentAgreementEndDate']['day'] = '01';
                    $event->setData($data);
                }
            });

        $builder
            ->add('title', 'hidden')
            ->add('id', 'hidden')
            ->add('save', 'submit');
    }

    protected function getValidationGroups()
    {
        return function (FormInterface $form) {

            /** @var $data \AppBundle\Entity\Report\AssetProperty */
            $data = $form->getData();
            $validationGroups = ['property'];

            if ($data->getOwned() == 'partly') {
                $validationGroups[] = 'owned-partly';
            }

            if ($data->getHasMortgage() == 'yes') {
                $validationGroups[] = 'mortgage-yes';
            }

            if ($data->getIsRentedOut() == 'yes') {
                $validationGroups[] = 'rented-out-yes';
            }

            return $validationGroups;
        };
    }


    public function getName()
    {
        return 'asset';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'report-assets',
            'validation_groups' => $this->getValidationGroups(),
        ]);
    }

}
