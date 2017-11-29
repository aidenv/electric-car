<?php

namespace Yilinker\Bundle\BackendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\AbstractType;

use Yilinker\Bundle\BackendBundle\Form\Validator\Constraints\ValidDateScheduled;
use Yilinker\Bundle\BackendBundle\Form\Validator\Constraints\ValidDateEnd;
use Yilinker\Bundle\BackendBundle\Form\Validator\Constraints\ValidPromoUnits;

/**
 * Class PromoFormType
 * @package Yilinker\Bundle\FrontendBundle\Form\Type
 */
class PromoFormType extends AbstractType
{

    /**
     * Build Promo Form Validation
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add("title", "text", array(
                "constraints" => array(
                    new NotBlank(array(
                        "message" => "Promo name is required"
                    )),
                    new NotNull(array(
                        "message" => "Promo name is required"
                    )),
                    new ValidPromoUnits($options)
                )
            ))
            ->add("promoType", "entity", array(
                "class" => "YilinkerCoreBundle:PromoType",
                "property" => "promoTypeId",
                "constraints" => array(
                    new NotNull(array(
                        "message" => "Promo Type is required"
                    ))
                )
            ))
            ->add("advertisement", "text")
            ->add("isEnabled", "checkbox")
            ->add("dateStart", "text", array(
                "data_class" => "\DateTime",
                "constraints" => array(
                    new NotBlank(array(
                        "message" => "Promo name is required"
                    )),
                    new NotNull(array(
                        "message" => "Promo name is required"
                    ))
                )
            ))
            ->add("dateEnd", "text", array(
                "data_class" => "\DateTime",
                "constraints" => array(
                    new NotBlank(array(
                        "message" => "Promo name is required"
                    )),
                    new NotNull(array(
                        "message" => "Promo name is required"
                    )),
                    new ValidDateEnd($options)
                )
            ))
            ;
    }


    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            "data_class"      => "Yilinker\Bundle\CoreBundle\Entity\PromoInstance",
            "csrf_protection" => true,
            "promoType"       => null,
            "dateStart"       => null,
            "dateEnd"         => null,
            "products"        => array(),
            "excludedInstance"=> null,
            "format"          => "m-d-Y H:i:s"
        ));
    }

    public function getName()
    {
        return "promo";
    }
}
