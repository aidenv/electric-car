<?php

namespace Yilinker\Bundle\BackendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Carbon\Carbon;

class RefundHistoryFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateFrom', 'text', array(
                'attr' => array(
                    'class' => 'form-ui datePicker'
                ),
                'data' => Carbon::now()->subWeek()->format('m/d/Y'),
                'empty_data' => Carbon::now()->subWeek()->format('m/d/Y')
            ))
            ->add('dateTo', 'text', array(
                'attr' => array(
                    'class' => 'form-ui datePicker'
                ),
                'data' => Carbon::now()->format('m/d/Y'),
                'empty_data' => Carbon::now()->format('m/d/Y')
            ))
            ->add('q', 'text', array(
                'attr' => array(
                    'class' => 'form-ui',
                    'placeholder' => 'FullName / Email'
                ),
                'required' => false
            ))
        ;
    }

    public function getName()
    {
        return 'refund_history_filter';
    }
}