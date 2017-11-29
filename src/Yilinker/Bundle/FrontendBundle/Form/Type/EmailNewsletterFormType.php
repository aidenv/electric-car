<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueNewsletterEmail;

class EmailNewsletterFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', 'text', array(
            'constraints' => array(
                new NotBlank(array(
                    "message" => "Email field is is required"
                )),
                new NotNull(array(
                    "message" => "Email field is is required"
                )),
                new Email(array(
                    "message" => "Email Address is not valid"
                )),
                new UniqueNewsletterEmail(),
            ),
        ));
    }

    public function getName()
    {
        return 'email_newsletter';
    }
}
