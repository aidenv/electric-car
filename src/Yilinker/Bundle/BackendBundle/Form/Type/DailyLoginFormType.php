<?php

namespace Yilinker\Bundle\BackendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Url;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints\IsAlphanumericSpace;

/**
 * Class ImageFormType
 * @package Yilinker\Bundle\CoreBundle\Form\Type
 */
class DailyLoginFormType extends YilinkerBaseFormType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstMessage', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Message one is required"
                    )),
                    new NotNull(array(
                        "message" => "Message one is required"
                    ))
                )
            ))
            ->add('secondMessage', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Message two is required"
                    )),
                    new NotNull(array(
                        "message" => "Message two is required"
                    ))
                )
            ))
            ->add('images','file', array (
                'required' => false,
                'constraints' => array(
                    new All(array(
                        'constraints' => array(
                            new Image(array(
                                'maxSize'  => '2M',
                                'mimeTypes' => array(
                                    'png',
                                    'jpg',
                                    'image/jpeg',
                                    'image/png',
                                ),
                                'mimeTypesMessage' => 'Please upload a valid jpeg/png file',
                            ))
                        )
                    ))
                )
            ))
            ->add('firstBannerUrl', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "First banner url is required"
                    )),
                    new NotNull(array(
                        "message" => "First banner url is required"
                    )),
                    new Url(array(
                        "message" => "First banner url is not a valid url"
                    ))
                )
            ))
            ->add('secondBannerUrl', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Second banner url is required"
                    )),
                    new NotNull(array(
                        "message" => "Second banner url is required"
                    )),
                    new Url(array(
                        "message" => "Second banner url is not a valid url"
                    ))
                )
            ))
            ->add('thirdBannerUrl', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Third banner url is required"
                    )),
                    new NotNull(array(
                        "message" => "Third banner url is required"
                    )),
                    new Url(array(
                        "message" => "Third banner url is not a valid url"
                    ))
                )
            ))
        ;
    }

    public function getName()
    {
        return 'daily_login_form_type';
    }
}