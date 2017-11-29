<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Exception;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Yilinker\Bundle\MerchantBundle\Services\FileUpload\ProductFileUploader;
use Symfony\Component\Filesystem\Filesystem;

class EmptyTemporaryImageCommand extends ContainerAwareCommand
{
    
    const MAX_FILE_LIFETIME_HOURS = 3;

    protected function configure()
    {
        $this->setName('yilinker:empty:temporary-image')
             ->setDescription('Empty the temporary image directory');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $datetimeNow = Carbon::now();
        $expirationTime = $datetimeNow->subHours(self::MAX_FILE_LIFETIME_HOURS);
        $kernelDirectory = $this->getContainer()->get('kernel')->getRootDir();
        
        $uploadService =  $this->getContainer()->get('yilinker_core.service.upload.upload');
        $uploadDirectory = $uploadService->getUploadDirectory();
        $temporaryDirectory = $kernelDirectory.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."web".DIRECTORY_SEPARATOR.$uploadDirectory.DIRECTORY_SEPARATOR.ProductFileUploader::TEMP_FOLDER;
        $finder->files()->in($temporaryDirectory);
        $finder->date("< ".$expirationTime->format('Y-m-d H:i:s'));

        $fs = new Filesystem();
        foreach ($finder as $file) {
            $output->writeln("Deleted ".$file->getRealpath());
            $fs->remove(array($file));
        }
    }
        
}