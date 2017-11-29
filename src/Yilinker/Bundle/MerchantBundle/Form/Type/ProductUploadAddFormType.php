<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

class ProductUploadAddFormType extends YilinkerBaseFormType
{
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('user', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\User',
                'required' => true,
                'property' => 'name',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'Invalid User')
                    ),
                    new NotNull(
                        array('message' => 'Invalid User')
                    ),
                )
            ))
            ->add('name', 'text', array(
                'required' => true,
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Product Name field is required"
                    )),
                    new NotNull(array(
                        "message" => "Product Name field is required"
                    ))
                )
            ))
            ->add('shortDescription', 'text', array(
                'required' => true,
                'constraints' => array(
                    new Length(
                        array(
                            'max' => 155,
                            'maxMessage' => 'Short Description cannot be longer than {{ limit }} characters'
                        )
                    ),
                    new NotBlank(
                        array('message' => 'Short Description is required')
                    ),
                    new NotNull(
                        array('message' => 'Short Description is required')
                    ),
                ),
                'invalid_message' => 'Short Description is required'
            ))
            ->add('description', 'text', array(
                'required' => true,
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'Complete Description is required')
                    ),
                    new NotNull(
                        array('message' => 'Complete Description is required')
                    ),
                ),
                'invalid_message' => 'Complete Description is required'
            ))
            ->add('brand', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\Brand',
                'multiple' => false,
                'required' => true,
                'property' => 'name',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'No Brand is selected')
                    ),
                    new NotNull(
                        array('message' => 'No Brand is selected')
                    ),
                ),
                'invalid_message' => 'No Brand is selected'
            ))
            ->add('condition', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\ProductCondition',
                'multiple' => false,
                'required' => true,
                'property' => 'name',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'No Condition is selected')
                    ),
                    new NotNull(
                        array('message' => 'No Condition is selected')
                    ),
                ),
                'invalid_message' => 'No Condition is selected'
            ))
            ->add('productCategory', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\ProductCategory',
                'multiple' => false,
                'required' => true,
                'property' => 'name',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'No Category is selected')
                    ),
                    new NotNull(
                        array('message' => 'No Category is selected')
                    ),
                ),
                'invalid_message' => 'No Category is selected'
            ))
            ->add('isFreeShipping', 'text', array(
                'required' => false,
                'constraints' => array(
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Product Free Shipping is not valid'
                    ))
                )
            ))
            ->add('youtubeVideoUrl', 'text', array(
                'required' => false
            ))
            ->add('shippingCategory', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\ShippingCategory',
                'multiple' => false,
                'required' => false,
                'property' => 'name',
            ))
        ;

    }

    public function getName()
    {
        return 'product_upload_detail';
    }

}

