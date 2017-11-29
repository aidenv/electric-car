<?php
namespace Yilinker\Bundle\CoreBundle\Command;

use RecursiveDirectoryIterator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate either page for the env or all
 *
 * Generate all : php app/frontend/console cms:generate --all --environment=dev
 * Generate one page : php app/frontend/console cms:generate <page> <platform (web/mobile)> --environment=dev
 *
 * Class GenerateCmsXmlCommand
 * @package Yilinker\Bundle\FrontendBundle\Command
 */
class GenerateCmsXmlCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('yilinker:cms:generate')
            ->setDescription('Generate XML files for app contents')
            ->addArgument(
                'page',
                InputArgument::OPTIONAL,
                'Page to generate'
            )
            ->addArgument(
                'platform',
                InputArgument::OPTIONAL,
                'Platform of page to generate'
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'Version to generate'
            )
            ->addArgument(
                'country',
                InputArgument::OPTIONAL,
                'Country to generate'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'If set, all pages will be generated'
            )
            ->addOption(
                'environment',
                null,
                InputOption::VALUE_REQUIRED,
                'Environment to generate'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $contentDir = $this->getContainer()->get('kernel')->locateResource("@YilinkerCoreBundle/Resources/content");

        $page = $input->getArgument('page');
        $platform = $input->getArgument('platform');
        $version = $input->getArgument('version');
        $country = $input->getArgument('country');

        if(is_null($country)){
            $country = "ph";
        }

        $environment = $input->getOption('environment');
        $all = $input->getOption('all');

        $text = "";

        if($all){
            if($environment == "dev" || $environment == "prod"){

                $directoryIterator = new RecursiveDirectoryIterator($contentDir.DIRECTORY_SEPARATOR."dist");

                foreach($directoryIterator as $item){
                    if($item->isFile()){

                        $fileName = $item->getFilename();

                        $details = explode(".", $fileName);

                        $text .= $this->copyFile(
                            $contentDir,
                            $fileName,
                            $environment,
                            $details[0],
                            $details[1],
                            $details[2],
                            $country
                        );
                    }
                }
            }
            else{
                $text = "Invalid Environment.";
            }
        }
        else{

            $file = $contentDir.DIRECTORY_SEPARATOR."dist".DIRECTORY_SEPARATOR.$page.".".$version.".".$platform.".xml.dist";
            if(!is_null($page) && !is_null($platform) && !is_null($environment)){
                if(file_exists($file)){
                    $text .= $this->copyFile(
                        $contentDir,
                        $page.".".$version.".".$platform.".xml.dist",
                        $environment,
                        $page,
                        $version,
                        $platform
                    );
                }
                else{
                    $text = "Content not found.";
                }
            }
        }

        $output->writeln($text);
    }

    /**
     * Generates the file
     *
     * @param $contentDir
     * @param $origFileName
     * @param $env
     * @param $directory
     * @param $fileName
     * @return string
     */
    private function copyFile(
        $contentDir,
        $origFileName,
        $env,
        $directory,
        $version,
        $fileName,
        $country = "ph"
    ){
        $log = "";

        if(!file_exists($contentDir.DIRECTORY_SEPARATOR.$country)){
            mkdir($contentDir.DIRECTORY_SEPARATOR.$country, 0777);
        }

        if(!file_exists($contentDir.DIRECTORY_SEPARATOR.$country.DIRECTORY_SEPARATOR.$env)){
            mkdir($contentDir.DIRECTORY_SEPARATOR.$country.DIRECTORY_SEPARATOR.$env, 0777);
        }

        if(!file_exists($contentDir.DIRECTORY_SEPARATOR.$country.DIRECTORY_SEPARATOR.$env.DIRECTORY_SEPARATOR.$directory)){
            mkdir($contentDir.DIRECTORY_SEPARATOR.$country.DIRECTORY_SEPARATOR.$env.DIRECTORY_SEPARATOR.$directory, 0777);
        }

        if(!file_exists($contentDir.DIRECTORY_SEPARATOR.$country.DIRECTORY_SEPARATOR.$env.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$version)){
            mkdir($contentDir.DIRECTORY_SEPARATOR.$country.DIRECTORY_SEPARATOR.$env.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$version, 0777);
        }

        $mainFile = $contentDir.DIRECTORY_SEPARATOR."dist".DIRECTORY_SEPARATOR.$origFileName;
        $generatedFile = $contentDir.DIRECTORY_SEPARATOR.$country.DIRECTORY_SEPARATOR.$env.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$version.DIRECTORY_SEPARATOR.$fileName.".xml";

        if(!copy($mainFile, $generatedFile)){
            $log = "Failed to generate ".ucfirst($fileName)." content for ".ucfirst($env)." ".$version.". \n";
        }
        else{
            $log = ucfirst($fileName)." content of ".ucfirst($directory)." generated for ".ucfirst($env)." ".$version.". \n";
        }

        return $log;
    }
}
