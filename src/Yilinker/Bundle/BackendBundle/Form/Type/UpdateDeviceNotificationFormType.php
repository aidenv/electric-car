<?php

namespace Yilinker\Bundle\BackendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\AbstractType;

use Yilinker\Bundle\BackendBundle\Form\Validator\Constraints\ValidRecipient;
use Yilinker\Bundle\BackendBundle\Form\Validator\Constraints\ValidDateScheduled;
use Yilinker\Bundle\BackendBundle\Form\Validator\Constraints\ValidTarget;
use Yilinker\Bundle\BackendBundle\Form\Validator\Constraints\ValidTargetType;
use Yilinker\Bundle\BackendBundle\Form\Validator\Constraints\ValidNotification;

class UpdateDeviceNotificationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("deviceNotification", "entity", array(
                "class" => "YilinkerCoreBundle:DeviceNotification"
            ))
            ->add("title", "text", array(
                "constraints" => array(
                    new NotBlank(array(
                        "message" => "Title is required"
                    )),
                    new NotNull(array(
                        "message" => "Title is required"
                    )),
                    new Length(array(
                        "max" => 50,
                        "maxMessage" => "Title can only be up to {{ limit }} characters",
                    )),
                    new ValidNotification($options)
                )
            ))
            ->add("recipient", "text", array(
                "constraints" => array(
                    new NotBlank(array(
                        "message" => "Recipient is required"
                    )),
                    new NotNull(array(
                        "message" => "Recipient is required"
                    )),
                    new ValidRecipient()
                )
            ))
            ->add("targetType", "text", array(
                "constraints" => array(
                    new NotBlank(array(
                        "message" => "Recipient is required"
                    )),
                    new NotNull(array(
                        "message" => "Recipient is required"
                    )),
                    new ValidTargetType()
                )
            ))
            ->add("target", "text", array(
                "constraints" => array(
                    new ValidTarget($options)
                )
            ))
            ->add("message", "text", array(
                "constraints" => array(
                    new NotBlank(array(
                        "message" => "Message is required"
                    )),
                    new NotNull(array(
                        "message" => "Message is required"
                    )),
                    new Length(array(
                        "max" => 100,
                        "maxMessage" => "Message can only be up to {{ limit }} characters",
                    )),
                )
            ))
            ->add("dateScheduled", "text", array(
                "constraints" => array(
                    new NotBlank(array(
                        "message" => "Date scheduled is required"
                    )),
                    new NotNull(array(
                        "message" => "Date scheduled is required"
                    )),
                    new ValidDateScheduled()
                )
            ))
            ->add("isActive", "checkbox", array(
                "value" => 0
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            "csrf_protection"       => true,
            "target"                => null,
            "targetType"            => null,
            "deviceNotificationId"  => null
        ));
    }

    public function getName()
    {
        return "admin_update_notification";
    }

}
