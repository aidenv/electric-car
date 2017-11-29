<?php

namespace Yilinker\Bundle\FrontendBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class for creating an OAuth2 Client via the command line
 */
class CreateOauthClientCommand extends ContainerAwareCommand
{
    /**
     * Configure step
     */
    protected function configure()
    {
        $this
            ->setName('yilinker:oauth-server:client:create')
            ->setDescription('Creates a new client')
            ->addOption(
                'redirect-uri',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sets redirect uri for client. Use this option multiple times to set multiple redirect URIs.',
                null
            )
            ->addOption(
                'grant-type',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sets allowed grant type for client. Use this option multiple times to set multiple grant types.',
                null
            )
            ->addOption(
                'client-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Client name',
                ''
            )
            ->setHelp(
                "The <info>%command.name%</info> command creates a new client.
<info>php %command.full_name% [--redirect-uri=...] [--grant-type=...]  [--client-name=...] name</info>" // intentionally like this to fix format
            );
    }

    /**
     * Execution step
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientManager = $this->getContainer()->get('fos_oauth_server.client_manager.default');
        $client = $clientManager->createClient();
        $client->setRedirectUris($input->getOption('redirect-uri'));
        $client->setAllowedGrantTypes($input->getOption('grant-type'));
        $client->setClientName($input->getOption('client-name'));
        
        $clientManager->updateClient($client);
        $output->writeln(
            sprintf(
                'Added a new client with public id <info>%s</info>, secret <info>%s</info>',
                $client->getPublicId(),
                $client->getSecret()
            )
        );
    }
}
