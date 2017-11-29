<?php

namespace Yilinker\Bundle\BackendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * Class PayoutBatchFileFormType
 * @package Yilinker\Bundle\BackendBundle\Form\Type
 */
class PayoutBatchFileFormType extends YilinkerBaseFormType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('receipt','file', array(
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
                ))
                ->add('payoutBatchHead', 'entity', array (
                    'class' => 'Yilinker\Bundle\CoreBundle\Entity\PayoutBatchHead',
                    'required' => true,
                    'property' => 'name',
                    'constraints' => array(
                        new NotBlank(
                            array('message' => 'Invalid PayoutBatchHeadId')
                        ),
                        new NotNull(
                            array('message' => 'Invalid PayoutBatchHeadId')
                        ),
                    )
                ));
    }

    public function getName()
    {
        return 'payout_batch_file_upload';
    }

}