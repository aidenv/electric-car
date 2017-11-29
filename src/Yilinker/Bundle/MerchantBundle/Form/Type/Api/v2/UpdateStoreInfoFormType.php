<?php
namespace Yilinker\Bundle\MerchantBundle\Form\Type\Api\v2;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ExistingUserImage;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\IsStoreEditable;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\UniqueStoreName;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\UniqueSlug;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\ValidSlug;

use Doctrine\ORM\EntityRepository;

class UpdateStoreInfoFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add("storeName", "text", array(
                    "constraints" => array(
                        new NotBlank(array(
                            "message" => "Store name should not be blank"
                        )),
                        new NotNull(array(
                            "message" => "Store name should not be blank"
                        )),
                        new Length(array(
                            "min" => 1,
                            "max" => 50,
                            "minMessage" => "Store name is required",
                            "maxMessage" => "Store name can only be up to {{ limit }} characters",
                        )),
                        new UniqueStoreName($options),
                        new IsStoreEditable(array_merge($options, array(
                            "type" => "storeName"
                        )))
                    ),
                ))
                ->add("storeSlug", "text", array(
                    "constraints" => array(
                        new NotBlank(array(
                            "message" => "Store slug should not be blank"
                        )),
                        new NotNull(array(
                            "message" => "Store slug should not be blank"
                        )),
                        new Length(array(
                            "min" => 1,
                            "max" => 45,
                            "minMessage" => "Store slug is required",
                            "maxMessage" => "Store slug can only be up to {{ limit }} characters",
                        )),
                        new ValidSlug(),
                        new UniqueSlug($options),
                        new IsStoreEditable(array_merge($options, array(
                            "type" => "storeSlug"
                        )))
                    ),
                ))
                ->add("storeDescription", "text", array(
                    "constraints" => array(
                        new NotBlank(array(
                                "message" => "Store description field is required"
                        )),
                        new NotNull(array(
                                "message" => "Store description field is required"

                        )),
                        new Length(array(
                            "min" => 1,
                            "max" => 1024,
                            "minMessage" => "Store description is required",
                            "maxMessage" => "Store description can only be up to {{ limit }} characters",
                        ))
                    )
                ))
                ->add("profilePhoto", "text", array(
                    "constraints" => array(
                        new ExistingUserImage(array_merge($options, array(
                            "type" => UserImage::IMAGE_TYPE_AVATAR 
                        )))
                    )
                ))
                ->add("coverPhoto", "text", array(
                    "constraints" => array(
                        new ExistingUserImage(array_merge($options, array(
                            "type" => UserImage::IMAGE_TYPE_BANNER 
                        )))
                    )
                ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            "csrf_protection" => true,
            "user" => null,
        ));
    }

    public function getName()
    {
        return "update_store_info_v2";
    }
}
