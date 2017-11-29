<?php

namespace Yilinker\Bundle\CoreBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateUserReferralCodeCommand extends ContainerAwareCommand
{
    const USERS_PER_ITERATION = 100;
    
    protected function configure()
    {
        $this
            ->setName('yilinker:user:generate-referral-code')
            ->setDescription('Generate referral code for user without referral code')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $accountManager = $container->get('yilinker_core.service.account_manager');

        $limit = self::USERS_PER_ITERATION;
        $userCount = 0;        
        do{
            $users = $em->getRepository('YilinkerCoreBundle:User')
                        ->qb()
                        ->filterByEmptyReferralCode()
                        ->setMaxResults($limit)
                        ->orderBy('this.userId', 'ASC')
                        ->getResult();
            $userCount = count($users);

            foreach ($users as $user) {
                $accountManager->generateReferralCode($user);
                $output->writeln("<info>Generated referral code for user id: ".$user->getUserId()."</info>");
            }
        }
        while($userCount > 0);
              
        $output->writeln("<info>Update complete!</info>");
    }
}