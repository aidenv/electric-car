<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * Class PayoutBatchFileFormType
 * @package Yilinker\Bundle\BackendBundle\Form\Type
 */
class FileImagePdfFormType extends YilinkerBaseFormType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('files','file', array (
                    'multiple' => true,
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
                                        'application/pdf',
                                        'application/x-pdf',
                                    ),
                                    'mimeTypesMessage' => 'Please upload a valid jpeg/png/pdf file',
                                ))
                            )
                        ))
                    )
                ));
    }

    public function getName()
    {
        return 'core_file_image_pdf';
    }

}