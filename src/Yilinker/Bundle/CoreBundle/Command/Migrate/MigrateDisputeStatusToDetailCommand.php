<?php

namespace Yilinker\Bundle\CoreBundle\Command\Migrate;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateDisputeStatusToDetailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('yilinker:dispute:data-migrate')
             ->setDescription('Move order product status of Dispute to DisputeDetail');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();

        do {
            $disputeDetails = $em->getRepository('YilinkerCoreBundle:DisputeDetail')
                                 ->findBy(array('orderProductStatus' => null), array(), 10);
            foreach ($disputeDetails as $detail) {
                $dispute = $detail->getDispute();

                $output->writeln("Writing Dispute detai: ".$detail->getDisputeDetailId());
                $detail->setOrderProductStatus($dispute->getOrderProductStatus());

                $em->flush();
            }
        } while (count($disputeDetails) > 0);

        $output->writeln("");
        $output->writeln("Migrate Complete!");
    }

}
