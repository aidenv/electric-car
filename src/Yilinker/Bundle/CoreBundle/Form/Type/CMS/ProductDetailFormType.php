<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\CMS;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Symfony\Component\Form\CallbackTransformer;
use Gedmo\Sluggable\Util\Urlizer;

/**
 * Class ProductDetailFormType
 *
 * @package Yilinker\Bundle\CoreBundle\Form\Type
 */
class ProductDetailFormType extends YilinkerBaseFormType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('sectionId', 'text', array(
                    'required' => true
                ))
                ->add('title', 'text', array(
                    'required' => true
                ))
                ->add('products', 'entity', array(
                    'class' => 'Yilinker\Bundle\CoreBundle\Entity\Product',
                    'multiple' => true,
                    'required' => true,
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Please select at least one product."
                        )),
                        new NotNull(array(
                            "message" => "Please select at least one product."
                        )),
                    )
                ))
                ->add('featuredProductBanner', 'file', array(
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
                                    'mimeTypesMessage' => 'Please upload a valid jpeg or png file',
                                ))
                            )
                        ))
                    )
                ))
                ->add('featuredProductUrl', 'text', array(
                    'required' => false
                ))
                ->add('featuredProductBannerFileName', 'text')
                ->add('isNewFeaturedProductBanner', 'checkbox', array(
                    'required' => false,
                ))
                ->add('innerPageBannerSrc', 'file', array(
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
                                    'mimeTypesMessage' => 'Please upload a valid jpeg or png file',
                                ))
                            )
                        ))
                    )
                ))
                ->add('innerPageBannerUrl', 'text', array(
                    'required' => false
                ))
                ->add('innerPageBannerFileName', 'text')
                ->add('isNewInnerPageBanner', 'checkbox', array(
                    'required' => false,
                ))
                ->add('applyImmediate', 'checkbox', array(
                    'required' => false,
                ))
        ;

        $this->addTransformers($builder, $options);
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
        return 'core_cms_product_detail';
    }

    private function addTransformers(FormBuilderInterface $builder, array $options)
    {
        $urlizer = new Urlizer;

        $builder->get('title')->addModelTransformer(new CallbackTransformer(
            function($title) use ($urlizer) {
                return $urlizer->urlize($title);
            },
            function($title) use ($urlizer) {
                return $urlizer->urlize($title);
            }
        ));
    }
}
