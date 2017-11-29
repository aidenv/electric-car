<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

class UserImageFormType extends YilinkerBaseFormType
{
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('image',
                      'file',
                      array(
                          'required' => true,
                          'constraints' => array(
                              new NotBlank(array(
                                  "message" => "No image file found."
                              )),
                              new NotNull(array(
                                  "message" => "No image file found."
                              )),
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
        return 'user_image';
    }

}
