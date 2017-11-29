<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\CoreBundle\Form\DataTransformer\Package\PackageToWaybillTransformer;
use Symfony\Component\Form\AbstractType;

/**
 * Class PackageStatusFormType
 *
 * @package Yilinker\Bundle\CoreBundle\Form\Type
 */
class PackageStatusFormType extends AbstractType
{

    private $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('dateUpdated', 'datetime',
                      array(
                          'widget' => 'single_text',
                          'format' => 'yyyy-MM-dd HH:mm:ss',
                          'required' => true,
                          'constraints' => array(
                              new NotBlank(array(
                                  "message" => "Package date update must be set",
                              )),
                          ),
                ))
                ->add('personFullname', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Person fullname can only be up to {{ limit }} characters',
                        )),
                    ),
                ))
                ->add('packageStatus', 'entity', array(
                    'class' => 'Yilinker\Bundle\CoreBundle\Entity\PackageStatus',
                    'label' => 'Package status',
                    'required' => true,
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Package status is required"
                        )),
                        new NotNull(array(
                            "message" => "Package status is required"
                        ))
                    ),
                    'invalid_message' => 'Invalid Package Status'
                ))
                ->add('package', 'text', array(
                    'required' => true,
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Invalid waybill number"
                        )),
                        new NotNull(array(
                            "message" => "Invalid waybill number"
                        ))
                    ),
                    'invalid_message' => 'Invalid waybill number',
                ))                
                ->add('contactNumber', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 25,
                            'maxMessage' => 'Contact number can only be up to {{ limit }} characters',
                        )),
                    ),
                ))
                ->add('address', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Address can only be up to {{ limit }} characters',
                        )),
                    ),
                ));

        $this->addFieldTransformers($builder);
    }

    public function addFieldTransformers($builder)
    {
        $package = new PackageToWaybillTransformer($this->em, 'YilinkerCoreBundle:Package');

        $builder->get('package')->addModelTransformer($package);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'package_status_update';
    }

}
