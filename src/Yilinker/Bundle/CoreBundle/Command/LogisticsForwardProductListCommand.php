<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Yilinker\Bundle\CoreBundle\Entity\ApiAccessLog;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LogisticsForwardProductListCommand extends ContainerAwareCommand
{
    const DEFAULT_BULK_PER_PAGE = 10;

    protected function configure()
    {
        $this->setName('yilinker:synchronize:logistics-product')
             ->setDescription('Forward Product list to express')
             ->addOption(
                 'perPage',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'Buld products per Page'
             )
             ->addOption(
                 'ignoreLast',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'Ignore last API Access Log'
             )
             ->addOption(
                 'manufacturerProductId',
                 null,
                 InputOption::VALUE_REQUIRED| InputOption::VALUE_IS_ARRAY,
                 'Manufacturer product ID'
             )
             ->addOption(
                 'dateFrom',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'Date from filter. This has precedence over ignoreLast. [YYYY-MM-DD H:i:s]'
             );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit','-1');
        $perPage = $input->getOption('perPage');
        $manufacturerProductIds = $input->getOption('manufacturerProductId');

        $perPage = $perPage ? $perPage : self::DEFAULT_BULK_PER_PAGE;
        $ignoreAccessLog = $input->getOption('ignoreLast') == 'true';
        
        $dateFromParam =  $input->getOption('dateFrom');
        $dateFromParam = $dateFromParam ? \DateTime::createFromFormat('Y-m-d H:i:s', $dateFromParam) : null;
        
        $logisticService =  $this->getContainer()->get('yilinker_core.logistics.yilinker.express');
        $entityManager =  $this->getContainer()->get('doctrine')->getManager();
        $lastAccessLog = $entityManager->getRepository('YilinkerCoreBundle:ApiAccessLog')
                                       ->getLastAccessLogByType(ApiAccessLog::API_TYPE_EXPRESS_PRODUCT);

        $dateFrom = null;
        $dateTo = new \DateTime();

        if($dateFromParam){
            $dateFrom = $dateFromParam;
        }        
        else if(!$ignoreAccessLog && $lastAccessLog){
            $dateFrom = $lastAccessLog->getDateAdded();
        }

        $number = $entityManager->getRepository('YilinkerCoreBundle:ManufacturerProduct')
                                ->getActiveManufacturerProducts(
                                    null, null, null, null, null,
                                    null, null, $dateFrom, $dateTo, true, true,
                                    $manufacturerProductIds
                                );

        $iteration = 0;
        $totalForwardedData = 0;
        while($number > 0){
            $number = $number - $perPage;
            $offset = $perPage * $iteration;
            $manufacturerProducts = $entityManager->getRepository('YilinkerCoreBundle:ManufacturerProduct')
                                                  ->getActiveManufacturerProducts(
                                                      null, null, null, null, null,
                                                      $offset, $perPage, $dateFrom, $dateTo,
                                                      false, true, $manufacturerProductIds
                                                  );

            $forwardedData = $logisticService->forwardProductList($manufacturerProducts);
            if($forwardedData['isSuccessful']){
                $totalForwardedData += count($forwardedData['data']['data']);
                $output->writeln(
                    "\nForwarded the following data (".count($forwardedData['data']['data'])."): ".json_encode($forwardedData['data']['data'])
                );
            }
            $iteration++;
        }
        
        $message = "\nCompleted data forwarding (".$totalForwardedData.") ... [OK]";
        $dateNow = new \DateTime();

        if($totalForwardedData > 0){
            $newApiAccessLog = new ApiAccessLog();
            $newApiAccessLog->setDateAdded($dateNow);
            $newApiAccessLog->setApiType(ApiAccessLog::API_TYPE_EXPRESS_PRODUCT);
            $newApiAccessLog->setData($message);
            $entityManager->persist($newApiAccessLog);
            $entityManager->flush();
        }

        $output->writeln($message);
    }

}
