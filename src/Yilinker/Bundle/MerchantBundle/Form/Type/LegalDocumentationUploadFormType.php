<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * Class LegalDocumentationUploadFormType
 * @package Yilinker\Bundle\MerchantBundle\Form\Type
 */
class LegalDocumentationUploadFormType extends YilinkerBaseFormType
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
                                'maxSize'  => '10M',
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
                ))
        );
    }

    public function getName()
    {
        return 'legal_document_upload';
    }
}