<?php
namespace Yilinker\Bundle\MerchantBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\Name;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\AlphaSpace;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\UniqueSlug;
use Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints\ValidSlug;

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
                ->add('nickname', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 45,
                            'maxMessage' => 'Nickname can only be up to {{ limit }} characters',
                        )),
                        new Name(array("message" => "Nickname contains invalid characters"))
                    ),
                    'required' => false
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
                            'maxMessage' => 'Store slug can only be up to {{ limit }} characters',
                        )),
                        new ValidSlug(),
                        new UniqueSlug(array('user' => $options['user']))
                    )
                ))
                ->add('categoryIds', 'entity', array(
                    'class' => 'YilinkerCoreBundle:ProductCategory',
                    'multiple' => true,
                    'choices' => $options['productCategories'],
                    'required' => false,
                    'invalid_message' => "Category not found."
                ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions(array(
            'csrf_protection' => false,
            'user' => null,
            'productCategories' => array()
        ));

        $resolver->setDefaults($this->getDefaultOptions());
    }

    public function getName()
    {
        return 'update_merchant_info';
    }
}
