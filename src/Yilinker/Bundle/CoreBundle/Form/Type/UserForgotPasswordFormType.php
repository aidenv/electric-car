<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Form\AbstractType;

class UserForgotPasswordFormType extends AbstractType
{
    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('request', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Field is required."
                    )),
                    new NotNull(array(
                        "message" => "Field is required."
                    )),
                ),
                'attr' => array(
                    'placeholder' => 'Enter your email/contact number here'
                )
            ))
        ;

        $rs = $this->container->get('request_stack');
        $request = $rs->getCurrentRequest();

        if($request){
            $session = $request->getSession();
            $locale = $session->get('_locale');
        }

        if (isset($locale) && $locale == 'cn') {
            $builder->add('captcha', 'captcha', array(
                'attr' => array(
                    'class' => 'form-ui',
                    'placeholder' => 'Enter the captcha code here'
                )
            ));
        }
        else {
            $builder->add('grecaptcha', 'hidden', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Captcha is required."
                    )),
                    new NotNull(array(
                        "message" => "Captcha is required."
                    ))
                ),
            ));
        }
    }

    public function getName()
    {
        return 'user_forgot_password';
    }
}
