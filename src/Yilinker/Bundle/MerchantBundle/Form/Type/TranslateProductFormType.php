<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * Class TranslateProductAddFormType
 *
 * @package Yilinker\Bundle\MerchantBundle\Form\Type
 */
class TranslateProductFormType extends YilinkerBaseFormType
{
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user', 'entity', array(
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
                            'max' => 250,
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
        ;

    }

    public function getName()
    {
        return 'translate_product';
    }

}

