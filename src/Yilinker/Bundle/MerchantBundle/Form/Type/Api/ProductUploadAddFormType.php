<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type\Api;

use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;

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
            ->add('brand', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\Brand',
                'required' => true,
                'multiple' => false,
                'property' => 'name',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'No Brand is selected')
                    ),
                    new NotNull(
                        array('message' => 'No Brand is selected')
                    ),
                ),
                'invalid_message' => 'Invalid Brand'
            ))
            ->add('productCategory', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\ProductCategory',
                'required' => true,
                'multiple' => false,
                'property' => 'name',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'No Category is selected')
                    ),
                    new NotNull(
                        array('message' => 'No Category is selected')
                    ),
                ),
                'invalid_message' => 'Invalid Category'
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
            ->add('description', 'text', array(
                'required' => false,
            ))
            ->add('shortDescription', 'text', array(
                'required' => false,
                'constraints' => array(
                    new Length(array(
                        'max' => 155,
                        'maxMessage' => 'Short Description field can only be up to {{ limit }} characters',
                    )),
                ),
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
                'invalid_message' => 'Invalid Condition'
            ))
            ->add('isFreeShipping', 'text', array(
                'required' => false,
                'constraints' => array(
                    new Type(array(
                        'type' => 'numeric',
                        'message' => 'Declared Product Free Shipping is not valid'
                    ))
                )
            ))
        ;

    }

    public function getName()
    {
        return 'api_product_upload_add';
    }

}
