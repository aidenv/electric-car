<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueEmail;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\YilinkerPassword;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidContactNumber;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\OTP;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;

class UserGuestFormType extends YilinkerBaseFormType
{
    private $accountManager;
    private $frontendAccountManager;
    private $authService;
    private $em;

    public function __construct($accountManager, $frontendAccountManager, $authService, $em)
    {
        $this->accountManager = $accountManager;
        $this->frontendAccountManager = $frontendAccountManager;
        $this->authService = $authService;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['signup_completion']) {
            $builder
                ->add('plainPassword', 'repeated', array(
                    'label' => 'Password',
                    'type' => 'password',
                    'invalid_message' => 'Passwords do not match',
                    'first_options' => array('label' => 'Password'),
                    'second_options' => array('label' => 'Confirm Password'),
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Password field is required"
                        )),
                        new NotNull(array(
                            "message" => "Password field is required"
                        )),
                        new Length(array(
                                'min' => 8,
                                'minMessage' => 'Password must be at least {{ limit }} characters',
                                'max' => 25,
                                'maxMessage' => 'Password can only be up to {{ limit }} characters',
                        )),
                        new YilinkerPassword(),
                    )
                ))
                ->add('referralCode', 'text', array(
                    'mapped'    => false,
                    'label'     => 'Referral Code',
                    'required'  => false
                ))
            ;

            $this->addSignupListener($builder);
        }
        else {
            $builder
                ->add('firstName', null, array(
                    'label' => 'First Name',
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "First name is required"
                        )),
                        new NotNull(array(
                            "message" => "First name is required"
                        ))
                    ),
                    'required' => true
                ))
                ->add('lastName', null, array(
                    'label' => 'Last Name',
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Last name is required"
                        )),
                        new NotNull(array(
                            "message" => "Last name is required"
                        ))
                    ),
                    'required' => true
                ))
                ->add('contactNumber', null, array(
                    'label' => 'Contact Number',
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Contact Number is required"
                        )),
                        new NotNull(array(
                            "message" => "Contact Number is required"
                        )),
                        new ValidContactNumber()
                    ),
                    'required' => true
                ))
                ->add('confirmationCode', 'text', array(
                    'mapped'        => false,
                    'constraints'   => array(
                        new NotNull(array(
                            "message" => "Confirmation code is required"
                        )),
                        new OTP(array(
                            'type' => OneTimePasswordService::OTP_TYPE_GUEST_CHECKOUT
                        ))
                    ),
                    'attr' => array(
                        'style' => 'display:none'
                    )
                ))
            ;
        }
    }

    public function addSignupListener($builder)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
            $form = $event->getForm();
            if ($form->isValid()) {
                $user = $form->getData();
                $referralCode = $form['referralCode']->getData();
                $referrer = null;
                if ($referralCode) {
                    $tbUser = $this->em->getRepository('YilinkerCoreBundle:User');
                    $referrer = $tbUser->findOneBy(compact('referralCode'));
                    if (!$referrer) {
                        $referralError = new FormError('Referral code does not exist');
                        $form->get('referralCode')->addError($referralError);

                        return false;
                    }
                }
                $user->setUserType(User::USER_TYPE_BUYER);
                $this->accountManager->registerUser($user);

                if ($referrer) {
                    $this->frontendAccountManager->addReferrer($user, $referrer);
                }
                $this->authService->authenticateUser($user, 'buyer', array('ROLE_BUYER'));
            }
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'        => 'Yilinker\Bundle\CoreBundle\Entity\User',
            'signup_completion' => false,
            'excludeUserId' => null,
            'userType' => User::USER_TYPE_BUYER
        ));
    }

    public function getName()
    {
        return 'user_guest';
    }
}
