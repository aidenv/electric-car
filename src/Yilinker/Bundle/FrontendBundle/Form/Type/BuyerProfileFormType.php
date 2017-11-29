<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidContactNumber;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
 
class BuyerProfileFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstname', 'text', array(
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "First Name is required"
                        )),
                        new NotNull(array(
                            "message" => "First Name is required"
                        )),    
                    )
                ))
                ->add('lastname', 'text', array(
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Last Name is required"
                        )),
                        new NotNull(array(
                            "message" => "Last Name is required"
                        ))
                    )
                ))->add('defaultAddress', 'entity', array(
                    'class' => 'Yilinker\Bundle\CoreBundle\Entity\UserAddress',
                    'property' => 'userAddressId',
                    'constraints' => array(
                        new NotBlank(
                            array('message' => 'Default Address must be set')
                        ),
                        new NotNull(
                            array('message' => 'Default Address must be set')
                        ),
                    ),
                    'invalid_message' => 'Invalid user address',
                ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions([
            'buyerId' => null,
        ]);
        $resolver->setDefaults($this->getDefaultOptions());
    }

    public function getName()
    {
        return 'buyer_profile';
    }
}
