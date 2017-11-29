<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type\Api\v3;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

use Doctrine\ORM\EntityRepository;

use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\ValidYoutubeURL;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\ValidTempProductImage;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\ValidUnits;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;

use Carbon\Carbon;

class ProductUploadFormType extends AbstractType
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
            ->add("youtubeVideoUrl", "text", array(
                "constraints" => array(
                    new ValidYoutubeURL()
                )
            ))
            ->add("condition", "entity", array(
                "class" => "YilinkerCoreBundle:ProductCondition",
                "property" => "productConditionId",
                "invalid_message" => "Invalid product condition",
                "constraints" => $this->getDefaultConstraint($options["isDraft"], "Product condition")
            ))
            ->add("productCategory", "entity", array(
                "class" => "YilinkerCoreBundle:ProductCategory",
                "property" => "productCategoryId",
                "invalid_message" => "Invalid product category",
                "query_builder" => function (EntityRepository $er) {
                    return $er->createQueryBuilder("pc")
                              ->where("pc.isDelete = :isDelete")
                              ->andWhere("pc.productCategoryId <> :rootCategory")
                              ->setParameter(":isDelete", false)
                              ->setParameter(":rootCategory", ProductCategory::ROOT_CATEGORY_ID);
                },
                "constraints" => $this->getDefaultConstraint($options["isDraft"], "Product category")
            ))
            ->add("shippingCategory", "entity", array(
                "class" => "YilinkerCoreBundle:ShippingCategory",
                "property" => "shippingCategoryId",
                "invalid_message" => "Invalid product shipping category",
                "constraints" => array(
                )
            ))
            ->add("brand", "text", array(
                "mapped" => false,
                "data" => "",
                "constraints" => array(
                )
            ))
            ->add("productGroups", "text", array(
                "mapped" => false,
                "data" => "[]",
                "empty_data" => "[]",
                "constraints" => array(
                )
            ))
            ->add("productImages", "text", array(
                "mapped" => false,
                "data" => "[]",
                "empty_data" => "[]",
                "constraints" => array(
                    new  ValidTempProductImage($options)
                )
            ))
            ->add("productUnits", "text", array(
                "mapped" => false,
                "data" => "[]",
                "empty_data" => "[]",
                "constraints" => array(
                    new ValidUnits($options)
                )
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            "data_class" => "Yilinker\Bundle\CoreBundle\Entity\Product",
            "csrf_protection" => true,
            "user" => null,
            "product" => null,
            "isDraft" => false,
            "isCreate" => true,
        ));
    }

    public function getName()
    {
        return "api_v3_product_upload";
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
