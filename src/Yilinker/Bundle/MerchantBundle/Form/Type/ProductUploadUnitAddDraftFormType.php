<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type;

use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

class ProductUploadUnitAddDraftFormType extends YilinkerBaseFormType
{
    public function buildForm (FormBuilderInterface $builder, array $options)
    {

        $builder->add('quantity', 'text', array(
                'required' => false,
                'constraints' => array(
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Declared Product Quantity is not valid'
                    ))
                )
            ))
            ->add('sku', 'text', array(
                'required' => false
            ))
            ->add('price', 'text', array(
                'required' => false,
                'constraints' => array(
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Declared Product Price is not valid'
                    ))
                )
            ))
            ->add('discountedPrice', 'text', array(
                'required' => false,
                'constraints' => array(
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Declared Product Price is not valid'
                    ))
                )
            ))
            ->add('weight', 'text', array(
                'required' => false,
                'constraints' => array(
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Declared Product weight is not valid'
                    ))
                )
            ))
            ->add('length', 'text', array(
                'required' => false,
                'constraints' => array(
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Declared Product length is not valid'
                    ))
                )
            ))
            ->add('width', 'text', array(
                'required' => false,
                'constraints' => array(
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Declared Product width is not valid'
                    ))
                )
            ))
            ->add('height', 'text', array(
                'required' => false,
                'constraints' => array(
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Declared Product height is not valid'
                    ))
                )
            ))
            ->add('status', 'text', array(
                'required' => true,
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'No Status is selected')
                    ),
                    new NotNull(
                        array('message' => 'No Status is selected')
                    ),
                ),
            ))
        ;

    }

    public function getName()
    {
        return 'product_upload_unit_draft';
    }

}
