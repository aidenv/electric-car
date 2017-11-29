<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\Name;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\UniqueSlug;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\ValidSlug;

/**
 * Class AffiliateAccreditationApplicationFormType
 * @package Yilinker\Bundle\MerchantBundle\Form\Type
 */
class AffiliateAccreditationApplicationFormType extends YilinkerBaseFormType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', 'text', array(
                'constraints' => array(
                    new Length(array(
                        'min' => 1,
                        'max' => 512,
                        'minMessage' => 'First name is required',
                        'maxMessage' => 'First name can only be up to {{ limit }} characters',
                    )),
                    new Name(array("message" => "First name contains invalid characters"))
                ),
                'required' => false
            ))
            ->add('lastName', 'text', array(
                'constraints' => array(
                    new Length(array(
                        'min' => 1,
                        'max' => 512,
                        'minMessage' => 'Last name is required',
                        'maxMessage' => 'Last name can only be up to {{ limit }} characters',
                    )),
                    new Name(array("message" => "Last name contains invalid characters"))
                ),
                'required' => false
            ))
            ->add('storeName', 'text', array(
                'constraints' => array(
                    new Length(array(
                        'max' => 150,
                        'maxMessage' => 'Store name can only be up to {{ limit }} characters',
                    )),
                    new Name(array("message" => "Store name contains invalid characters"))
                ),
                'required' => false
            ))
            ->add('storeDescription', 'text', array(
                'constraints' => array(
                    new Length(array(
                        'max' => 1024,
                        'maxMessage' => 'Store description can only be up to {{ limit }} characters',
                    ))
                )
            ))
            ->add('storeSlug', 'text', array(
                'constraints' => array(
                    new Length(array(
                        'max' => 45,
                        'maxMessage' => 'Store link can only be up to {{ limit }} characters',
                    )),
                    new ValidSlug(),
                    new UniqueSlug(array('user' => $options['user']))
                )
            ))
            ->add('tin', 'text', array(
                'constraints' => array(
                    new Length(array(
                        'max'        => 45,
                        'maxMessage' => 'Tin can only be up to {{ limit }} characters'
                    ))
                )
            ))
        ->add('tinImage', 'file', array(
            'required' => false,
            'constraints' => array(
                new All(array(
                    'constraints' => array(
                        new Image(array(
                            'maxSize'  => '10M',
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
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions(array(
            'csrf_protection' => false,
            'user' => null,
        ));

        $resolver->setDefaults($this->getDefaultOptions());
    }

    public function getName()
    {
        return 'affiliate_accreditation_application_information';
    }

}
