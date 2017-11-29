<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

class UserIdentificationCardFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', 'file', array(
                        'constraints' => array(
                            new File(array(
                                'maxSize'   => '2M',
                                'mimeTypes' => array(
                                    'image/jpeg',
                                    'image/png',
                                ),
                                'mimeTypesMessage' => 'Please upload a valid Image',
                                'maxSizeMessage' => 'The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}',
                            )),
                        )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions([
            'csrf_protection' => false,
        ]);

        $resolver->setDefaults($this->getDefaultOptions());
    }

    public function getName()
    {
        return 'core_user_document';
    }

}

