<?php

namespace Yilinker\Bundle\BackendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Url;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints\IsAlphanumericSpace;

/**
 * Class CmsBrandFormType
 * @package Yilinker\Bundle\BackendBundle\Form\Type
 */
class CmsBrandFormType extends YilinkerBaseFormType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('brand', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\Brand',
                'multiple' => false,
                'required' => true,
                'property' => 'name',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'Invalid Brand')
                    ),
                    new NotNull(
                        array('message' => 'Invalid Brand')
                    ),
                ),
                'invalid_message' => 'Invalid Brand'
            ))
            ->add('products', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\Product',
                'multiple' => true,
                'required' => true,
                'property' => 'name',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'Invalid Product')
                    ),
                    new NotNull(
                        array('message' => 'Invalid Product')
                    ),
                ),
                'invalid_message' => 'Invalid Product'
            ))
            ->add('image','file', array (
                'required' => false,
                'constraints' => array(
                    new All(array(
                        'constraints' => array(
                            new Image(array(
                                'maxSize'  => '2M',
                                'mimeTypes' => array(
                                    'png',
                                    'jpg',
                                    'image/jpeg',
                                    'image/png',
                                ),
                                'mimeTypesMessage' => 'Please upload a valid jpeg/png file',
                            ))
                        )
                    ))
                )
            ))
            ->add('description', 'text')
            ->add('imageFileName', 'text')
            ->add('isImageNew', 'text')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            "csrf_protection"   => false,
        ));
    }

    public function getName()
    {
        return 'cms_brand_form';
    }

}
