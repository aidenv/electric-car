<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\v1;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\AlphaNumUnderscore;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\YilinkerPassword;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueEmail;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\Name;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\AlphaSpace;
use Yilinker\Bundle\CoreBundle\Entity\User;

class UserRegisterFormType extends AbstractType
{
    private $kernel = null;

    public function setKernel($kernel)
    {
        $this->kernel = $kernel;
    }

    public function userType()
    {
        $userType = null;

        if ($this->kernel) {
            if ($this->kernel->getName() == 'frontend') {
                $userType = User::USER_TYPE_BUYER;
            }
            elseif ($this->kernel->getName() == 'merchant') {
                $userType = User::USER_TYPE_SELLER;
            }
        }

        return $userType;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $passwordConstraint = array();
        if($options['validatePassword']){
            $passwordConstraint = array(
                new NotBlank(array(
                    "message" => "Password field is required"
                )),
                new NotNull(array(
                    "message" => "Password field is required"
                )),
                new Length(array(
                    'min' => 8,
                    'minMessage' => 'Password must be atleast {{ limit }} characters',
                    'max' => 25,
                    'maxMessage' => 'Password can only be up to {{ limit }} characters',
                )),
                new YilinkerPassword(),
            );
        }
        
        $builder->add('plainPassword', 'repeated', array(
                        'label' => 'Password',
                        'type' => 'password',
                        'invalid_message' => 'Passwords do not match',
                        'first_options' => array('label' => 'Password'),
                        'second_options' => array('label' => 'Confirm Password'),
                        'constraints' => $passwordConstraint,
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
                ->add('contactNumber', 'text', array(
                        'constraints' => array(
                            new NotBlank(array(
                                "message" => "Contact No field is required"
                            )),
                            new NotNull(array(
                                "message" => "Contact No is required"
                            )),
                            new Length(array(
                                'min' => 10,
                                'max' => 20,
                                'minMessage' => 'Contact No must be atleast {{ limit }} characters',
                                'maxMessage' => 'Contact No can only be up to {{ limit }} characters',
                            )),
                            new UniqueContactNumber($options),
                            new ValidContactNumber()
                        )
                ))
                ->add('firstName', 'text', array(
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "First Name is required"
                        )),
                        new NotNull(array(
                            "message" => "First Name is required"
                        )),
                        new Name(array("message" => "First name contains invalid characters")),
                    )
                ))
                ->add('lastName', 'text', array(
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Last Name is required"
                        )),
                        new NotNull(array(
                            "message" => "Last Name is required"
                        )),
                        new Name(array("message" => "Last name contains invalid characters")),
                    )
                ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'excludeUserId'    => null,
            'validatePassword' => true,
            'userType'         => $this->userType(),
        ));
    }

    public function getName()
    {
        return 'core_v1_user_add';
    }
}
