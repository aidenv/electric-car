<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\File;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;

class ProductPickupFormType extends YilinkerBaseFormType
{
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('pickupDatetime', 'datetime',
                      array(
                          'widget' => 'single_text',
                          'format' => 'yyyy-MM-dd HH:mm:ss',
                          'constraints' => array(
                              new NotBlank(array(
                                  "message" => "Pickup schedule must be set",
                              ))
                          ),
                      ))
                ->add('pickupRemark', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 512,
                            'maxMessage' => 'Pickup remark can only be up to {{ limit }} characters',
                        )),
                    ),
                ))
                ->add('invoiceNumber', 'text', array(
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Invoice number is required"
                        )),
                        new NotNull(array(
                            "message" => "Invoice number is required"
                        ))
                    )
                ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions(array(
            'csrf_protection' => false
        ));

        $resolver->setDefaults($this->getDefaultOptions());
    }

    public function getName()
    {
        return 'api_product_pickup';
    }

}
