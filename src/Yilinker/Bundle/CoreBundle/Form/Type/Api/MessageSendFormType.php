<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

class MessageSendFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('message', 'textarea', array(
                        'constraints' => array(
                            new NotBlank(array(
                                "message" => "Message field is required"
                            )),
                            new NotNull(array(
                                "message" => "Message field is required"
                            ))
                        )
                    ))
                ->add('recipient', 'text', array(
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Recipient id is required"
                        )),
                        new NotNull(array(
                            "message" => "Recipient id is required"
                        ))
                    )
                ))
                ->add('isImage', 'text')
                ->add('add', 'submit');
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions(array(
            'csrf_protection' => false
        ));

        $resolver->setDefaults($this->getDefaultOptions());
    }

    public function getName()
    {
        return 'message_send';
    }
}

