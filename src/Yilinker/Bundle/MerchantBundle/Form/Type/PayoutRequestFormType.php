<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\OTP;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Range;

class PayoutRequestFormType extends AbstractType
{
    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @TODO:
         * Refactor, This should be injected as a form option instead
         * to prevent errors on cli actions such as generating i18n form errors
         */
        $storeService = $this->container->get('yilinker_core.service.entity.store');
        $store = $storeService->getStore();
        $availableBalance = $store? $store->service->getAvailableBalance() : null;

        $builder
            ->add('requestedAmount', 'text', array(
                'attr' => array(
                    'class'         => 'form-ui',
                    'placeholder'   => '0.00'
                ),
                'constraints' => array(
                    new Regex(array(
                        'pattern' => '/\d+\.?\d*/',
                        'message' => 'Invalid amount'
                    )),
                    new Range(array(
                        'min'           => 100,
                        'max'           => $availableBalance? $availableBalance : 0,
                        'minMessage'    => 'Withdraw amount has a minimum of P100.00 and should not exceed the available balance',
                        'maxMessage'    => 'Withdraw amount has a minimum of P100.00 and should not exceed the available balance'
                    ))
                )
            ))
            ->add('payoutRequestMethod', 'choice', array(
                'choices' => array(
                    1 => 'Deposit to Bank',
                    2 => 'Bank Cheque'
                ),
                'expanded' => true,
                'multiple' => false,
                'data'     => 1
            ))
            ->add('confirmationCode', 'text', array(
                'attr' => array(
                    'class'         => 'form-ui',
                    'placeholder'   => 'Enter your confirmation code here'
                ),
                'mapped' => false,
                'constraints' => array(
                    new OTP(array(
                        'type' => OneTimePasswordService::OTP_TYPE_PAYOUT_REQUEST
                    ))
                )
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\PayoutRequest'
        ));
    }

    public function getName()
    {
        return 'payout_request';
    }
}
