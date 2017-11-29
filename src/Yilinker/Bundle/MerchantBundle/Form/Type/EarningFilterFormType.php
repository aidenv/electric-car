<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Repository\EarningRepository;
use Carbon\Carbon;

class EarningFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $now = Carbon::now();
        $lastweek = Carbon::now()->subWeek();

        $builder
            ->add('qCriteria', 'choice', array(
                'choices'   => EarningRepository::getFilterCriterias(),
                'attr'      => array(
                    'class' => 'form-ui ui single selection dropdown inverted'
                )
            ))
            ->add('q', 'text', array(
                'attr' => array(
                    'class'         => 'form-ui inverted',
                    'placeholder'   => 'Search here'
                ),
                'required' => false
            ))
            ->add('type', 'choice', array(
                'expanded' => true,
                'multiple' => true,
                'choices'  => EarningType::getEarningTypes()
            ))
            ->add('status', 'choice', array(
                'expanded' => true,
                'multiple' => true,
                'choices'  => Earning::getStatuses()
            ))
            ->add('daterange', 'text', array(
                'attr' => array(
                    'class' => 'form-ui inverted align-center sales-report-daterange block'
                ),
                'data' => $lastweek->format('m/d/Y').' - '.$now->format('m/d/Y')
            ))
        ;
    }

    public function getName()
    {
        return 'earning_filter';
    }
}