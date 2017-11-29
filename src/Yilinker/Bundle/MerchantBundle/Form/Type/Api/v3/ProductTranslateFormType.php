<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type\Api\v3;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\ValidTempProductImage;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\ValidVariants;

use Carbon\Carbon;

class ProductTranslateFormType extends AbstractType
{
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("name", "text", array(
                "constraints" => $this->getDefaultConstraint($options["isDraft"], "Product name")
            ))
            ->add("shortDescription", "textarea", array(
                "constraints" => $this->getDefaultConstraint($options["isDraft"], "Short description")
            ))
            ->add("description", "textarea", array(
                "constraints" => $this->getDefaultConstraint($options["isDraft"], "Description")
            ))
            ->add("productImages", "text", array(
                "mapped" => false,
                "data" => "[]",
                "empty_data" => "[]",
                "constraints" => array(
                    new  ValidTempProductImage($options)
                )
            ))
            ->add("productVariants", "text", array(
                "mapped" => false,
                "data" => "[]",
                "empty_data" => "[]",
                "constraints" => array(
                     new ValidVariants($options)
                )
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            "data_class" => "Yilinker\Bundle\CoreBundle\Entity\Product",
            "csrf_protection" => true,
            "product" => null,
            "isCreate" => true,
            "isDraft" => false,
            "defaultValue" => null
        ));
    }

    public function getName()
    {
        return "api_v3_product_translate";
    }

    private function getDefaultConstraint($isDraft, $fieldName)
    {
        if(!$isDraft){
            return array(
                    new NotBlank(
                        array(
                            "message" => "{$fieldName} is required"
                        )
                    ),
                    new NotNull(
                        array(
                            "message" => "{$fieldName} is required"
                        )
                    )
            );
        }

        return array();
    }
}
