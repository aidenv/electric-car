<?php

namespace Yilinker\Bundle\BackendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\All;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * Class PayoutFormType
 * @package Yilinker\Bundle\FrontendBundle\Form\Type
 */
class PayoutFormType extends YilinkerBaseFormType
{
    /**
     * Build Payout Form Validation
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('orderProductIds', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\OrderProduct',
                'multiple' => true,
                'required' => true,
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Order Products are required"
                    )),
                    new NotNull(array(
                        "message" => "Order Products are required"
                    ))
                ),
                'invalid_message' => 'Invalid Order Product',
            ))
            ->add('depositSlips', 'file', array(
                'multiple' => true,
                'constraints' => array(
                    new All(array(
                        'constraints' => array(
                            new File(array(
                                'maxSize'   => '2M',
                                'mimeTypes' => array(
                                    'image/jpeg',
                                    'image/png',
                                ),
                                'mimeTypesMessage' => 'Please upload a valid image',
                            )),
                        )
                    ))
                 ),
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions(array(
        ));

        $resolver->setDefaults($this->getDefaultOptions());
    }

    /**
     * Get Form name
     * @return string
     */
    public function getName()
    {
        return 'backend_payout_form';
    }

}
