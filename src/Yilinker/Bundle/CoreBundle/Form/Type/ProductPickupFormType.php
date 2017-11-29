<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * Class CreateNewCaseFormType
 * @package Yilinker\Bundle\CoreBundle\Form\Type
 */
class ProductPickupFormType extends YilinkerBaseFormType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('pickupDatetime', 'datetime',
                      array(
                          'widget' => 'single_text',
                          'format' => 'yyyy-MM-dd HH:mm:ss',
                          'constraints' => array(
                              new NotBlank(array(
                                  "message" => "Pickup schedule must be set",
                              )),
                              new GreaterThanOrEqual("now"),
                          ),
                      ))
                ->add('pickupRemark', 'textarea', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 512,
                            'maxMessage' => 'Pickup remark can only be up to {{ limit }} characters',
                        )),
                    ),
                ));

        $orderProductFieldParam = array(
            'class' => 'Yilinker\Bundle\CoreBundle\Entity\OrderProduct',
            'choice_label' => 'orderProductId',
            'label' => 'Order Products',
            'multiple' => true,
            'required' => true,
            'expanded' => true,
            'constraints' => array(
                new NotBlank(array(
                    "message" => "At least one order product is required"
                )),
                new NotNull(array(
                    "message" => "At least one order product is required"
                ))
            ),
            'invalid_message' => 'Invalid Order Product'
        );

        if($options['orderProducts'] !== null){
            $orderProductFieldParam['choices'] = $options['orderProducts'];
        }

        $builder->add('orderProducts', 'entity', $orderProductFieldParam); 
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions([
            'orderProducts' => null,
            'pickupSchedule' => array(),
        ]);
        $resolver->setDefaults($this->getDefaultOptions());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'core_product_pickup';
    }

}
