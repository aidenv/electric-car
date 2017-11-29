<?php
namespace Yilinker\Bundle\MerchantBundle\Form\Type\Api\v2;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotNull;

use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\IsValidIdEditable;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\IsTinEditable;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueEmail;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\Name;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidReferralCode;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\LegalDocInTmp;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;

use Doctrine\ORM\EntityRepository;

class UpdateUserInfoFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add("firstName", "text", array(
                    "constraints" => array(
                        new NotBlank(array(
                            "message" => "First name should not be blank"
                        )),
                        new NotNull(array(
                            "message" => "First name should not be blank"
                        )),
                        new Length(array(
                            "min" => 1,
                            "max" => 50,
                            "minMessage" => "First name is required",
                            "maxMessage" => "First name can only be up to {{ limit }} characters",
                        )),
                        new Name(array("message" => "First name contains invalid characters"))
                    ),
                ))
                ->add("lastName", "text", array(
                    "constraints" => array(
                        new NotBlank(array(
                            "message" => "Last name should not be blank"
                        )),
                        new NotNull(array(
                            "message" => "Last name should not be blank"
                        )),
                        new Length(array(
                            "min" => 1,
                            "max" => 50,
                            "minMessage" => "Last name is required",
                            "maxMessage" => "Last name can only be up to {{ limit }} characters",
                        )),
                        new Name(array("message" => "Last name contains invalid characters"))
                    ),
                ))
                ->add('email', 'email', array(
                        'constraints' => array(
                            new NotBlank(array(
                                    "message" => "Email Address field is required"
                            )),
                            new NotNull(array(
                                    "message" => "Email Address field is required"

                            )),
                            new Email(array(
                                    "message" => "Email Address is not valid"

                            )),
                            new UniqueEmail($options)
                        )
                ))
                ->add("tin", "text", array(
                        "constraints" => array(
                            new IsTinEditable($options)
                        )
                ))
                ->add("referralCode", "text", array(
                        "constraints" => array(
                            new ValidReferralCode($options)
                        ),
                ))
                ->add("validId", "text", array(
                        "constraints" => array(
                            new LegalDocInTmp($options),
                            new IsValidIdEditable($options)
                        ),
                ))
        ;
    }
                
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            "csrf_protection" => false,
            "user" => null,
            "excludeUserId" => null,
            "storeType" => Store::STORE_TYPE_RESELLER,
            "userType" => User::USER_TYPE_SELLER,
        ));
    }

    public function getName()
    {
        return "update_merchant_info_v2";
    }
}
