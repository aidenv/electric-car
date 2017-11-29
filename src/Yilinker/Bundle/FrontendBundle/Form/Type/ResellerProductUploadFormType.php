<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints\IsActiveManufacturerProduct;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

class ResellerProductUploadFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('manufacturerProductId', 'text', 
                      array(
                        'constraints' => array(
                            new NotBlank(array(
                                "message" => "Manufacturer Product field is required"
                            )),
                            new IsActiveManufacturerProduct(),
                        ),
                ))
                ->add('description', 'text', 
                      array(
                        'constraints' => array(
                            new Length(array(
                                    'max' => 2048,
                                    'maxMessage' => 'Description field can only be up to {{ limit }} characters',
                            ))

                        ),
                ));
    }

    public function getName()
    {
        return 'reseller_upload';
    }
    
}
