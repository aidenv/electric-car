<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\AlphaNumUnderscore;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * Class CreateNewCaseFormType
 * @package Yilinker\Bundle\CoreBundle\Form\Type
 */
class CreateNewCaseFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Description is required"
                    )),
                    new NotNull(array(
                        "message" => "Description is required"

                    )),
                )
            ))
            ->add('message', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Message is required"
                    )),
                    new NotNull(array(
                        "message" => "Message is required"

                    )),
                )
            ))
            ->add('orderProductStatus', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus',
                'multiple' => false,
                'required' => true,
                'property' => 'name',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'No Order Product Status is selected')
                    ),
                    new NotNull(
                        array('message' => 'No Order Product Status is selected')
                    ),
                ),
                'invalid_message' => 'Invalid Order Product Status'
            ))
            ->add('orderProductIds', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\OrderProduct',
                'multiple' => true,
                'required' => true,
                'property' => 'orderProductId',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'No Order Product is selected')
                    ),
                    new NotNull(
                        array('message' => 'No Order Product is selected')
                    ),
                ),
                'invalid_message' => 'Invalid Order Product'
            ))
            ->add('orderProductCancellationReasonId', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason',
                'required' => true,
                'property' => 'orderProductCancellationReason',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'No Reason is selected')
                    ),
                    new NotNull(
                        array('message' => 'No Reason is selected')
                    ),
                ),
                'invalid_message' => 'Invalid Reason'
            ))
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'api_create_new_case';
    }

}
