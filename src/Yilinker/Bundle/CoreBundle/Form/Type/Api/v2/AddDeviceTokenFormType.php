<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Api\v2;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

use Symfony\Component\Form\AbstractType;

class AddDeviceTokenFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('deviceToken', 'text', array(
            'constraints' => array(
                new NotBlank(array(
                    "message" => "Device token field is required"
                )),
                new NotNull(array(
                    "message" => "Device token is required"
                ))
            )
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            "csrf_protection" => false
        ));
    }

    public function getName()
    {
        return 'api_core_add_device_token_v2';
    }
}
