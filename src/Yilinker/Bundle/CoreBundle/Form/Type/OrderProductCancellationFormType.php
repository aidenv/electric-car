<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\AlphaNumUnderscore;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;

use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason;

/**
 * Class CreateNewCaseFormType
 * @package Yilinker\Bundle\CoreBundle\Form\Type
 */
class OrderProductCancellationFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if($options['orderProducts'] === null){
            /**
             * throw exception to avoid loading all order products when the form is used
             */
            throw new InvalidArgumentException("orderProducts option is required");
        }
                
        $userCancellationType = OrderProductCancellationReason::USER_TYPE_BUYER;
        if (isset($options['userCancellationType'])
            && $options['userCancellationType'] !== OrderProductCancellationReason::USER_TYPE_BUYER) {
            $userCancellationType = OrderProductCancellationReason::USER_TYPE_SELLER;
        }

        $builder
            ->add('reason', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason',
                'choice_label' => 'reason',
                'label' => 'Reason for Cancellation',
                'required' => true,
                'query_builder' => function(EntityRepository $er) use ($userCancellationType) {
                    $qb = $er->createQueryBuilder('opr')
                             ->where('opr.userType = :userCancellationType')
                             ->setParameter('userCancellationType', $userCancellationType);

                    return $qb;
                },
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Cancellation reason is required"
                    )),
                    new NotNull(array(
                        "message" => "Cancellation reason is required"

                    )),
                )
            ));
        
        $orderProductFieldParam = array(
            'class' => 'Yilinker\Bundle\CoreBundle\Entity\OrderProduct',
            'multiple' => true,
            'required' => true,
            'expanded' => true,
            'choice_label' => 'productName',
            'property' => 'orderProductId',
            'constraints' => array(
                new NotBlank(
                    array('message' => 'No Order Product is selected')
                ),
                new NotNull(
                    array('message' => 'No Order Product is selected')
                ),
            ),
            'invalid_message' => 'Invalid Order Product',
            'choices' => $options['orderProducts'],
        );

        $builder->add('orderProducts', 'entity', $orderProductFieldParam)
                ->add('remark', 'textarea', array(
                   'label' => 'Cancellation Remark',
                   'constraints' => array(
                        new NotBlank(array(
                            "message" => "Remark is required"
                        )),
                        new NotNull(array(
                            "message" => "Remark is required" 
                        )),
                    )
                ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions([
            'orderProducts' => array(),
            'userCancellationType' => OrderProductCancellationReason::USER_TYPE_BUYER
        ]);
        $resolver->setDefaults($this->getDefaultOptions());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'order_product_cancellation';
    }

}
