<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections;
use Carbon\Carbon;

/**
 * Class for synchronizing data
 */
class SynchronizeTradingDataCommand extends ContainerAwareCommand
{
    /**
     * Configure step
     */
    protected function configure()
    {
        $this
            ->setName('yilinker:synchronize:trading-data')
            ->setDescription('Synchronizes data between trading and online')
            ->addOption(
                'table',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Table Name'
            )            
            ->addOption(
                'perPage',
                null,
                InputOption::VALUE_REQUIRED,
                'Limit per Page',
                100
            )
            ->addOption(
                'page',
                null,
                InputOption::VALUE_REQUIRED,
                'Current Page',
                1
            )
            ->addOption(
                'ignoreLast',
                null,
                InputOption::VALUE_REQUIRED,
                'Ignore last API Access Log'
            )
            ->addOption(
                'ignoreField',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Ignore this field. Use in conjunction with table option.'
            )
            ->addOption(
                'dateFrom',
                null,
                InputOption::VALUE_REQUIRED,
                'Y-m-d H:i:s. This has precedence over ignoreLast.'
            )
            ->addOption(
                'dateTo',
                null,
                InputOption::VALUE_REQUIRED,
                'Y-m-d H:i:s.'
            )
            ->addOption(
                'searchField',
                null,
                InputOption::VALUE_REQUIRED,
                'Search field for searchable table'
            )
            ->addOption(
                'lookbackSeconds',
                null,
                InputOption::VALUE_REQUIRED,
                'Alternative way to set the datefrom by looking back by a predetermined number of seconds. This has precedence over ignoreLast and dateFrom.'
            )
            ->addOption(
                'skus',
                null,
                InputOption::VALUE_REQUIRED,
                'skus'
            )
        ;
        
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit','-1');
        $tables = $input->getOption('table', null);
        $perPage = $input->getOption('perPage');
        $page = $input->getOption('page'); 
        $ignoreAccessLog = $input->getOption('ignoreLast') == 'true';        
        $dateFrom = $input->getOption('dateFrom', null);
        if( null == $dateFrom ) {
            $startTime = time()-24*2600*30;
            $dateFrom = date('Y-m-d H:i:s', $startTime);
        }
        $dateFrom = $dateFrom ? \DateTime::createFromFormat('Y-m-d H:i:s', $dateFrom) : null;
        $dateTo = $input->getOption('dateTo', null);
        $dateTo = $dateTo ? \DateTime::createFromFormat('Y-m-d H:i:s', $dateTo) : null;
        $searchField = $input->getOption('searchField', null);
        $lookbackSeconds = $input->getOption('lookbackSeconds', null);
        $skus = $input->getOption('skus', array());
        if($lookbackSeconds){
            $dateFrom = Carbon::now()->subSeconds($lookbackSeconds);
        }
        

        $ignoreFields = $input->getOption('ignoreField', null);        
        $ignoreFields = count($ignoreFields) > 0 ? $ignoreFields : array();

        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $tradingService = $this->getContainer()->get('yilinker_core.import_export.yilinker.trading');

        if($tables === null || count($tables) === 0){
            $mappingTables = $tradingService->getApiMapping()->get('tables');
            $tables = array_keys($mappingTables);
        }

        $tradingService->setIgnoredFields($ignoreFields);
        foreach($tables as $table){
            $response = $tradingService->synchronizeApiData(
                $table, $perPage, $page, $ignoreAccessLog, $dateFrom, $dateTo, $searchField, $skus
            );
            $output->writeln("\nSynching ".$table.": ".json_encode($response));
        }
        
    }
    

}

