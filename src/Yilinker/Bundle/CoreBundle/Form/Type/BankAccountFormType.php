<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * Class BankAccountFormType
 *
 * @package Yilinker\Bundle\CoreBundle\Form\Type
 */
class BankAccountFormType extends YilinkerBaseFormType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('accountTitle', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Account title can only be up to {{ limit }} characters',
                        )),
                        new NotBlank(array(
                            "message" => "Account title field is required"
                        )),
                        new NotNull(array(
                            "message" => "Account title field is required"
                        )),
                    )
                ))
                ->add('accountName', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Account name can only be up to {{ limit }} characters',
                        )),
                        new NotBlank(array(
                            "message" => "Account name field is required"
                        )),
                        new NotNull(array(
                            "message" => "Account name field is required"
                        )),
                    )
                ))
                ->add('accountNumber', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 25,
                            'maxMessage' => 'Account Number can only be up to {{ limit }} characters',
                        )),
                        new NotBlank(array(
                            "message" => "Account number field is required"
                        )),
                        new NotNull(array(
                            "message" => "Account number field is required"
                        )),
                    )
                ))
                ->add('bank', 'entity', array(
                    'class' => 'Yilinker\Bundle\CoreBundle\Entity\Bank',
                    'required' => true,
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Account number field is required"
                        )),
                        new NotNull(array(
                            "message" => "Account number field is required"
                        )),
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('b')
                                  ->where('b.isEnabled = :enabled')
                                  ->setParameter('enabled', true);
                    },
                ))
        ;
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
        return 'core_bank_account';
    }
}
