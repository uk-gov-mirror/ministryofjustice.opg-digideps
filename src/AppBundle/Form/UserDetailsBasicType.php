<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class UserDetailsBasicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstname', 'text')
                ->add('lastname', 'text')
                ->add('save', 'submit');
    }
    
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults( [
            'translation_domain' => 'user-details',
            'validation_groups' => ['user_details_basic'],
        ]);
    }
    
    public function getPasswordValidationGroup()
    {
        return 'user_details_basic';
    }
    
    public function getName()
    {
        return 'user_details';
    }
}
