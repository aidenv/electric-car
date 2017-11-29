<?php

namespace Yilinker\Bundle\CoreBundle\Command\Translation;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand AS Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultProductUnitCountryCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('yilinker:translation:default_product_unit_country')
            ->setDescription('Adds a country PH to product units with no country before the globalization was applied production')
            ->addOption('fromID', 'id', InputOption::VALUE_OPTIONAL, 'start from what id (inclusive)', 1)
            ->addOption('productIds', 'productIds', InputOption::VALUE_OPTIONAL, 'product ids')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fromID = $input->getOption('fromID');
        $productIds = $input->getOption('productIds');
        $productIds = $productIds ? explode(',', $productIds): array();
        $container = $this->getContainer();
        $translatable = $container->get('yilinker_core.translatable.listener');
        $translatable->setCountry('en');
        $em = $container->get('doctrine.orm.entity_manager');
        $uow = $em->getUnitOfWork();
        $tbProductUnit = $em->getRepository('YilinkerCoreBundle:ProductUnit');
        $page = 1;
        $em->beginTransaction();
        if ($productIds) {
            $units = $tbProductUnit
                ->qb()
                ->andWhere('this.product IN (:productIds)')
                ->setParameter('productIds', $productIds)
                ->page($page++)
                ->getResult()
            ;
        }
        else {
            $units = $tbProductUnit
                ->qb()
                ->andWhere('this.productUnitId >= :id')
                ->setParameter('id', $fromID)
                ->page($page++)
                ->getResult()
            ;
        }
        while ($units) {
            foreach ($units as $unit) {
                $output->writeln('Adding Country PH for unit #'.$unit->getProductUnitId());
                $unit->setLocale('ph');
                $uow->scheduleForUpdate($unit);
            }
            $em->flush();
            $em->clear();
            $units = null;
            gc_collect_cycles();
            if (!($page % 10)) {
                $em->commit();
                $em->beginTransaction();
            }

            if ($productIds) {
                $units = $tbProductUnit
                    ->qb()
                    ->andWhere('this.product IN (:productIds)')
                    ->setParameter('productIds', $productIds)
                    ->page($page++)
                    ->getResult()
                ;
            }
            else {
                $units = $tbProductUnit
                    ->qb()
                    ->andWhere('this.productUnitId >= :id')
                    ->setParameter('id', $fromID)
                    ->page($page++)
                    ->getResult()
                ;
            }
        }
        $output->writeln("Process Completed!");
    }
}