<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueEmail;
use Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints\IsAlphanumericSpace;
use Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints\IsValidMobile;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
 
class UserDetailsUpdateFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('password', 'password', array(
                      'constraints' => array(
                            new Length(array(
                                    'min' => 6,
                                    'minMessage' => 'Password must be atleast {{ limit }} characters',
                                    'max' => 15,
                                    'maxMessage' => 'Password can only be up to {{ limit }} characters',
                            )),
                        )
                ))
                ->add('storeName', 'text', array(
                        'constraints' => array(
                            new IsAlphanumericSpace(),
                        )
                ))
                ->add('email', 'email', array(
                        'constraints' => array(
                            new Email(array(
                                    "message" => "Email Address is not valid"

                            )),
                            new UniqueEmail(array('excludedUserId' => $options['userId'])),
                        )
                ))
                ->add('contactNumber', 'text', array(
                        'constraints' => array(
                            new IsValidMobile(),
                        )
                ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions([
            'userId' => null,
        ]);
        $resolver->setDefaults($this->getDefaultOptions());
    }

    public function getName()
    {
        return 'user_details_update';
    }
}
