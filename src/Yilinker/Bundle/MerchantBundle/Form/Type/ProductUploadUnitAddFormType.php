<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\LessThan;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\UniqueProductSku;

class ProductUploadUnitAddFormType extends YilinkerBaseFormType
{
    public function buildForm (FormBuilderInterface $builder, array $options)
    {

        $builder->add('quantity', 'integer', array(
                'required' => true,
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'Quantity is required')
                    ),
                    new NotNull(
                        array('message' => 'Quantity is required')
                    ),
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Quantity is not valid'
                    )),
                    new GreaterThanOrEqual(array(
                        'value' => 0,
                        'message' => 'Quantity cannot be less than zero',
                    )),
                    new LessThan(array(
                        'value' => 1000,
                        'message' => 'Quantity must be less than 1000',
                    ))
                )
            ))
            ->add('sku', 'text', array(
                'required' => true,
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'SKU is required')
                    ),
                    new NotNull(
                        array('message' => 'SKU is required')
                    ),
                    new UniqueProductSku($options['userId'], $options['excludeProductUnitId'], $options['product'])
                )
            ))
            ->add('price', 'text', array(
                'required' => true,
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'Price is required')
                    ),
                    new NotNull(
                        array('message' => 'Price is required')
                    ),
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Price is not valid'
                    )),
                    new GreaterThanOrEqual(array(
                        'value' => 0,
                        'message' => 'Price cannot be less than zero'
                    ))
                )
            ))
            ->add('discountedPrice', 'text', array(
                'required' => false,
                'constraints' => array(
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Discounted price is not valid'
                    )),
                    new GreaterThanOrEqual(array(
                        'value' => 0,
                        'message' => 'Discounted price cannot be less than zero'
                    ))
                )
            ))
            ->add('weight', 'text', array(
                'required' => true,
                'constraints' => array(
                    new Length(
                        array(
                            'max' => 250,
                            'maxMessage' => 'Weight cannot be longer than {{ limit }} characters'
                        )
                    ),
                    new NotBlank(
                        array('message' => 'Weight is required')
                    ),
                    new NotNull(
                        array('message' => 'Weight is required')
                    ),
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Weight is not valid'
                    )),
                    new GreaterThan(array(
                        'value' => 0,
                        'message' => 'Weight cannot be less than {{ compared_value }}'
                    )),
                    new LessThan(array(
                        'value' => 1000,
                        'message' => 'Weight cannot be greater than {{ compared_value }}'
                    ))
                )
            ))
            ->add('length', 'text', array(
                'required' => true,
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'Length is required')
                    ),
                    new NotNull(
                        array('message' => 'Length is required')
                    ),
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Length is not valid'
                    )),
                    new GreaterThan(array(
                        'value' => 0,
                        'message' => 'Length cannot be less than {{ compared_value }}'
                    )),
                    new LessThan(array(
                        'value' => 1000,
                        'message' => 'Length cannot be greater than {{ compared_value }}'
                    ))
                )
            ))
            ->add('width', 'text', array(
                'required' => true,
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'Width is required')
                    ),
                    new NotNull(
                        array('message' => 'Width is required')
                    ),
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Width is not valid'
                    )),
                    new GreaterThan(array(
                        'value' => 0,
                        'message' => 'Width cannot be less than {{ compared_value }}'
                    )),
                    new LessThan(array(
                        'value' => 1000,
                        'message' => 'Width cannot be greater than {{ compared_value }}'
                    ))
                )
            ))
            ->add('height', 'text', array(
                'required' => true,
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'Height is required')
                    ),
                    new NotNull(
                        array('message' => 'Height is required')
                    ),
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Height is not valid'
                    )),
                    new GreaterThan(array(
                        'value' => 0,
                        'message' => 'Height cannot be less than {{ compared_value }}'
                    )),
                    new LessThan(array(
                        'value' => 1000,
                        'message' => 'Height cannot be greater than {{ compared_value }}'
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

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'userId'               => null,
            'excludeProductUnitId' => null,
            'product'              => null
        ));
    }

    public function getName()
    {
        return 'product_upload_unit';
    }

}
