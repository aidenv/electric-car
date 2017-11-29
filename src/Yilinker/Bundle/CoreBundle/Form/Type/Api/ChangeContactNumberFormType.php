<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidContactNumber;

class ChangeContactNumberFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('contactNumber', 'text', array(
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
                    'minMessage' => 'Contact number must be atleast {{ limit }} characters',
                    'maxMessage' => 'Contact number can only be up to {{ limit }} characters',
                )),
                new UniqueContactNumber($options),
                new ValidContactNumber()
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions(array(
            'csrf_protection' => false,
            'userId'          => null,
            'excludedUserId'  => null,
            'userType'        => null,
            'storeType'       => null
        ));

        $resolver->setDefaults($this->getDefaultOptions());
    }

    public function getName()
    {
        return 'core_change_contact_number';
    }
}
