<?php
namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\SwiftmailerBundle\Command\NewEmailCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Swift_Attachment;

/**
 * Stress test Command
 *
 * @package Yilinker\Bundle\FrontendBundle\Command
 */
 /**
  * usage: 
  * yilinker:email:send --from=jonathan.antivo@yilinker.ph --to=jonathan.antivo@yilinker.ph --subject='Transaction Details' --cc=jonathan.antivo@yilinker.ph --attachment=/home/jonathan/LOCALDEVDISK/script/sample.csv --body='details'
  */
class SwiftMailerExtensionCommand extends NewEmailCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('yilinker:email:send')
            ->setDescription('Send Simple Message')
            ->addOption('cc', null, InputOption::VALUE_OPTIONAL, 'CC',null)
            ->addOption('attachment', null, InputOption::VALUE_OPTIONAL, 'file attachment',null)
            ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailerServiceName = sprintf('swiftmailer.mailer.%s', $input->getOption('mailer'));
        if (!$this->getContainer()->has($mailerServiceName)) {
            throw new \InvalidArgumentException(sprintf('The mailer "%s" does not exist', $input->getOption('mailer')));
        }
        switch ($input->getOption('body-source')) {
            case 'file':
                $filename = $input->getOption('body');
                $content = file_get_contents($filename);
                if ($content === false) {
                    throw new \Exception('Could not get contents from ' . $filename);
                }
                $input->setOption('body', $content);
                break;
            case 'stdin':
                break;
            default:
                throw new \InvalidArgumentException('Body-input option should be "stdin" or "file"');
        }

        $message = $this->createMessage($input);
        $mailer = $this->getContainer()->get($mailerServiceName);
        $output->writeln(sprintf('<info>Sent %s emails<info>', $mailer->send($message)));

    }

    private function createMessage(InputInterface $input)
    {
        $message = \Swift_Message::newInstance(
            $input->getOption('subject'),
            $input->getOption('body'),
            $input->getOption('content-type'),
            $input->getOption('charset')
        );
        $message->setFrom($input->getOption('from'));
        $message->setTo($input->getOption('to'));
        
        if ($input->getOption('cc')) {
            $message->addCc($input->getOption('cc'));    
        }
        
        if ($input->getOption('attachment')) {
            $message->attach(Swift_Attachment::fromPath($input->getOption('attachment')));    
        }

        return $message;
    }                                                                                                                                               

}
