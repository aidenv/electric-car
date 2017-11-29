<?php

namespace Yilinker\Bundle\OAuthServerBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;

class AuthorizeFormType extends AbstractType
{    
    public function buildForm(FormBuilderInterface $builder, array $options)  
    {  
        $builder->add('allowAccess', 'submit', array(  
            'label' => 'Allow access',
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'      => 'Yilinker\Bundle\OAuthServerBundle\Form\Model\Authorize',
            'csrf_protection' => false,
        ));
    }
    
    public function getName()  
    {  
        return 'oauth_server_oauthorize_type';  
    }  
}