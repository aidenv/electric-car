<?php

namespace Yilinker\Bundle\CoreBundle\Command\ElasticSearch;

use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticPopulateStoreCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('elastic:populate:store')
            ->setDescription('populates store index type')
            ->addOption(
                'startPage',
                'sp',
                InputOption::VALUE_OPTIONAL,
                'startPage',
                1
            )
            ->addOption(
                'pages',
                'p',
                InputOption::VALUE_OPTIONAL,
                'pages',
                20
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'limit',
                500
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $em = $container->get('doctrine.orm.entity_manager');
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $tbStore = $em->getRepository('YilinkerCoreBundle:Store');

        $conn = $em->getConnection();
        $sql = 'SELECT store_id FROM Store  order by store_id ASC  LIMIT :limit OFFSET :offset';
        $stmt = $conn->prepare($sql);

        $page = $input->getOption('startPage') > 0 ? $input->getOption('startPage') - 1 : 0;
        $limit = (int)$input->getOption('limit');
        $offset = $page * $limit;
        $stmt->bindParam('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam('offset', $offset, \PDO::PARAM_INT);
        $pages = (int)$input->getOption('pages');
        $ctr = 0;
        while ($ctr++ < $pages && $store = $stmt->execute()) {
            $stmt->execute();
            $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($ids as $id) {
                $store = $tbStore->find($id);
                $container->get('fos_elastica.object_persister.yilinker_online.store')->replaceOne($store);
                $output->writeln("Indexed store #$id");
                $em->clear();
                gc_collect_cycles();
            }
            $offset = ++$page * $limit;
            $output->writeln('Memory Usage: '.(memory_get_peak_usage(true)/1000000).'MB');
        }

        $output->writeln('Finished indexing store!!!');
    }
} 