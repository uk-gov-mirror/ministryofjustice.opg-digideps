<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use AppBundle\Form\Type\SortCodeType;
use AppBundle\Form\Type\AccountNumberType;

class AccountType extends AbstractType
{
     public function buildForm(FormBuilderInterface $builder, array $options)
     {
         $builder ->add('bank', 'text')
                  ->add('openingDate', 'date', [ 'widget' => 'text',
                                                 'input' => 'datetime',
                                                 'format' => 'yyyy-MM-dd',
                                                 'invalid_message' => 'account.openingDate.invalidMessage'
                                          ])
                  ->add('openingBalance','number', [ 'grouping' => true, 'precision' => 2 ])
                  ->add('sortCode',new SortCodeType(), [ 'error_bubbling' => false ])
                  ->add('accountNumber', new AccountNumberType(), [ 'error_bubbling' => false ])
                  ->add('save', 'submit');
     }
     
     public function setDefaultOptions(OptionsResolverInterface $resolver)
     {
         $resolver->setDefaults( [
            'translation_domain' => 'report-accounts'
        ]);
     }
     
     public function getName()
     {
         return 'account';
     }
     
}