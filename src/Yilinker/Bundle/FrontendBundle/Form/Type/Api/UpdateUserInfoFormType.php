<?php
namespace Yilinker\Bundle\FrontendBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueBuyerSlug;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidSlug;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\Name;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\AlphaSpace;
use Symfony\Component\Validator\Constraints\File;

/**
 * Class UpdateUserInfoFormType
 * @package Yilinker\Bundle\FrontendBundle\Form\Type
 */
class UpdateUserInfoFormType extends YilinkerBaseFormType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('profilePhoto','file', array(
                    'constraints' => array(
                        new Image(array(
                            'maxSize'  => '10M',
                            'mimeTypes' => array(
                                'png',
                                'jpg',
                                'jpeg',
                                'image/jpg',
                                'image/jpeg',
                                'image/png'
                            ),
                            'mimeTypesMessage' => 'Profile photo should be in jpg, jpeg or png format',
                            'maxSizeMessage' => 'Profile photo cant exceed to 10MB file size.',
                        ))
                    )))
                ->add('coverPhoto','file', array(
                        'constraints' => array(
                            new Image(array(
                                'maxSize'  => '10M',
                                'mimeTypes' => array(
                                    'png',
                                    'jpg',
                                    'jpeg',
                                    'image/jpg',
                                    'image/jpeg',
                                    'image/png'
                                ),
                                'mimeTypesMessage' => 'Cover photo should be in jpg, jpeg or png format',
                                'maxSizeMessage' => 'Cover photo cant exceed to 10MB file size.',
                            ))
                )))
                ->add('firstName', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'min' => 1,
                            'max' => 512,
                            'minMessage' => 'First name is required',
                            'maxMessage' => 'First name can only be up to {{ limit }} characters',
                        )),
                        new Name(array("message" => "First name contains invalid characters")),
                    )
                ))
                ->add('lastName', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'min' => 1,
                            'max' => 512,
                            'minMessage' => 'Last name is required',
                            'maxMessage' => 'Last name can only be up to {{ limit }} characters',
                        )),
                        new Name(array("message" => "Last name contains invalid characters")),
                    )
                ))
                ->add('nickname', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 45,
                            'maxMessage' => 'Nickname can only be up to {{ limit }} characters',
                        )),
                        new Name(array("message" => "Nickname contains invalid characters")),
                    )
                ))
                ->add('gender', 'choice', array(
                    'invalid_message' => 'Invalid gender',
                    'choices'  => array('M' => 'Male', 'F' => 'Female'),
                    'required' => false
                ))
                ->add('birthdate', 'date', array(
                    'invalid_message' => 'Incorrect date format',
                    'widget' => 'single_text',
                    'format' => 'MM-dd-yyyy',
                ))
                ->add('plainPassword', 'repeated', array(
                    'type' => 'password',
                    'invalid_message' => 'Passwords do not match.',
                    'required' => true,
                    'first_options' => array('label' => 'Password'),
                    'second_options' => array('label' => 'Confirm Password'),
                    'constraints' => array(
                        new Length(array(
                            'min' => 8,
                            'minMessage' => 'Password must be atleast {{ limit }} characters',
                            'max' => 25,
                            'maxMessage' => 'Password can only be up to {{ limit }} characters',
                )))))
                ->add('contactNumber', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'min' => 10,
                            'max' => 20,
                            'minMessage' => 'Contact number must be atleast {{ limit }} characters',
                            'maxMessage' => 'Contact number can only be up to {{ limit }} characters',
                        )),
                        new ValidContactNumber()
                    )
                ))
                ->add('title', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Title can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('unitNumber', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 45,
                            'maxMessage' => 'Unit number can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('buildingName', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Building name can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('streetNumber', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 11,
                            'maxMessage' => 'Street number can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('streetName', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Street name can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('subdivision', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Subdivision can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('zipCode', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 45,
                            'maxMessage' => 'Zip code can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('streetAddress', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 1024,
                            'maxMessage' => 'Street Address can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('longitude', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Invalid longitude',
                        ))
                    )
                ))
                ->add('latitude', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Invalid latitude',
                        ))
                    )
                ))
                ->add('landline', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Invalid landline',
                        ))
                    )
                ))
                ->add('contactNumber', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'min' => 5,
                            'max' => 60,
                            'minMessage' => 'Contact number must be atleast {{ limit }} characters',
                            'maxMessage' => 'Contact number can only be up to {{ limit }} characters',
                        )),
                        new UniqueContactNumber(),
                        new ValidContactNumber()
                    )
                ))
                ->add('slug', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 45,
                            'maxMessage' => 'Slug can only be up to {{ limit }} characters',
                        )),
                        new ValidSlug(),
                        new UniqueBuyerSlug()
                    )
                ))
                ->add('userDocument', 'file', array(
                    'constraints' => array(
                        new File(array(
                            'maxSize'   => '5M',
                            'mimeTypes' => array(
                                    'application/pdf',
                                    'application/x-pdf',
                                    'image/jpeg',
                                    'image/png',
                            ),
                            'mimeTypesMessage' => 'Please upload a valid Image/PDF',
                        )),
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
            'allow_extra_fields' => true
        ));

        $resolver->setDefaults($this->getDefaultOptions());
    }

    public function getName()
    {
        return 'update_buyer_info';
    }
}
