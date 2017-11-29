<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * Class ProductUploadImageFormType
 * @package Yilinker\Bundle\FrontendBundle\Form\Type
 */
class ProductUploadImageFormType extends YilinkerBaseFormType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('images','file', array(
                'multiple' => true,
                'constraints' => array(
                    new All(array(
                        'constraints' => array(
                            new Image(array(
                                'maxSize'  => '5M',
                                'maxSizeMessage' => 'Image Max Size should not be greater than 3MB',
                                'mimeTypes' => array(
                                    'png',
                                    'jpg',
                                    'image/jpeg',
                                    'image/png'
                                ),
                                'mimeTypesMessage' => 'Please upload a valid jpeg or png Image',
                            ))
                        )
                    ))
                ))
        );
    }

    public function getName()
    {
        return 'product_upload_image';
    }

}
