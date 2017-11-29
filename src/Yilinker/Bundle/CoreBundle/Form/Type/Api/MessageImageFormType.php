<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\File;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

class MessageImageFormType extends YilinkerBaseFormType
{
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('image',
                      'file',
                      array(
                          'required' => true,
                          'constraints' => array(
                              new File(array(
                                  'maxSize' => '10M',
                                  'mimeTypes' => array(
                                      'image/jpg',
                                      'image/jpeg',
                                      'image/png',
                                  ),
                                 'mimeTypesMessage' => 'Please upload a valid jpg, jpeg or png image',
                              ))
                          )
                      ))
                ->add('save', 'submit', array('label' => 'Upload'));
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
        return 'message_image';
    }

}
