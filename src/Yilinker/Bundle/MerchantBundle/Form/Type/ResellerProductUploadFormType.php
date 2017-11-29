<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ResellerProductUploadFormType extends YilinkerBaseFormType
{

    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array(
                   'required' => true,
                   'constraints' => array(
                       new NotBlank(array(
                           "message" => "Product Name field is required"
                       )),
                       new NotNull(array(
                           "message" => "Product Name field is required"
                       ))
                   )
                ))
                ->add('description', 'text', array(
                    'required' => true,
                    'constraints' => array(
                        new NotBlank(
                            array('message' => 'Complete Description is required')
                        ),
                        new NotNull(
                            array('message' => 'Complete Description is required')
                        ),
                    ),
                    'invalid_message' => 'Invalid Complete Description'
                ))
                ->add('shortDescription', 'text', array(
                    'required' => true,
                    'constraints' => array(
                        new Length(array(
                            'max' => 155,
                            'maxMessage' => 'Short Description field can only be up to {{ limit }} characters',
                        )),
                        new NotBlank(
                            array('message' => 'Short Description is required')
                        ),
                        new NotNull(
                            array('message' => 'Short Description is required')
                        ),
                    ),
                    'invalid_message' => 'Invalid Short Description'
                ));
    }
        
    public function getName()
    {
        return 'reseller_product_upload_detail';
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

                   
}
