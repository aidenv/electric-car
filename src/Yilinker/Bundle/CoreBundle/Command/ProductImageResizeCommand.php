<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use DirectoryIterator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;
use Yilinker\Bundle\MerchantBundle\Services\FileUpload\ProductFileUploader;
use Yilinker\Bundle\CoreBundle\Model\SimpleImage;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\AwsS3;
/**
 * NOTE : make sure to own the uploads dir before executing this command
 */
class ProductImageResizeCommand extends ContainerAwareCommand
{
    private $images = array();

    protected function configure()
    {
        $this->setName('yilinker:productimage:resize')
             ->setDescription('Adjust product images dimensions (Make sure to own the uploads dir before executing this command)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Resize starting.");

        $di = new DirectoryIterator("web/assets/images/uploads/products");
        foreach ($di as $file) {
            $productDir = $file->getPathname();
            if($productDir != "web/assets/images/uploads/products/temp" && $productDir != "web/assets/images/uploads/products/description-image"){
                $dii = new DirectoryIterator($productDir);
                foreach($dii as $productFile){
                    if(!$productFile->isDir() && $productFile->isFile()){
                        $filename = $productFile->getFilename();
                        $pathname = $productFile->getPathname();
                        /** thumbnail */
                        $thumbnailDir = $productDir.DIRECTORY_SEPARATOR.ProductFileUploader::PRODUCT_FOLDER_THUMBNAIL;
                        @unlink($thumbnailDir);
                        @mkdir($thumbnailDir, 0777);
                        $this->createImageSizes(
                            $thumbnailDir.DIRECTORY_SEPARATOR.$filename,
                            ProductFileUploader::SIZE_THUMBNAIL_HEIGHT,
                            ProductFileUploader::SIZE_THUMBNAIL_WIDTH,
                            $pathname
                        );
                        /** small */
                        $smallDir = $productDir.DIRECTORY_SEPARATOR.ProductFileUploader::PRODUCT_FOLDER_SMALL;
                        @unlink($smallDir);
                        @mkdir($smallDir, 0777);
                        $this->createImageSizes(
                            $smallDir.DIRECTORY_SEPARATOR.$filename,
                            ProductFileUploader::SIZE_SMALL_HEIGHT,
                            ProductFileUploader::SIZE_SMALL_WIDTH,
                            $pathname
                        );
                        $mediumDir = $productDir.DIRECTORY_SEPARATOR.ProductFileUploader::PRODUCT_FOLDER_MEDIUM;
                        @unlink($mediumDir);
                        @mkdir($mediumDir, 0777);
                        $this->createImageSizes(
                            $mediumDir.DIRECTORY_SEPARATOR.$filename,
                            ProductFileUploader::SIZE_MEDIUM_HEIGHT,
                            ProductFileUploader::SIZE_MEDIUM_WIDTH,
                            $pathname
                        );
                        $largeDir = $productDir.DIRECTORY_SEPARATOR.ProductFileUploader::PRODUCT_FOLDER_LARGE;
                        @unlink($largeDir);
                        @mkdir($largeDir, 0777);
                        $this->createImageSizes(
                            $largeDir.DIRECTORY_SEPARATOR.$filename,
                            ProductFileUploader::SIZE_LARGE_HEIGHT,
                            ProductFileUploader::SIZE_LARGE_WIDTH,
                            $pathname
                        );
                        $output->writeln("Resize for ".$pathname." done.");
                    }
                }
            }
        }

        $this->uploadToCloud($output);

        $output->writeln("Resize done.");
    }

    private function createImageSizes($uploadDirectory, $resizeHeight, $resizeWidth, $pathname)
    {
        $container = $this->getContainer();

        $imageManipulator = $container->get("yilinker_core.service.image_manipulation");

        $imageManipulator->writeThumbnail(
            substr_replace($pathname, "", 0, 3), 
            substr_replace($uploadDirectory, "", 0, 3), 
            array(
            "filters" => array(
                "relative_resize" => array(
                    "heighten" => $resizeHeight,
                    "widen" => $resizeWidth
                ),
            ),
        ));

        array_push($this->images, $uploadDirectory);
    }

    private function uploadToCloud($output)
    {
        $output->writeln("Uploading to cloud.");

        $container = $this->getContainer();
        $filesystem = $container->get("photo_storage_filesystem");
        $kernelRootDirectory = $container->getParameter("kernel.root_dir").'/../../web';

        $adapter = $filesystem->getAdapter();

        if($adapter instanceof AwsS3){
            foreach ($this->images as $image){
                $imageDir = $kernelRootDirectory.substr_replace($image, "", 0, 3);
                $output->writeln("Uploading ".$image);
                $file = new File($imageDir);
                $adapter->setMetadata($imageDir, array('contentType' => $file->getMimeType()));
                $result = $adapter->write($imageDir, file_get_contents($file->getPathname()));

                if(!$result){
                    $output->writeln("Failed to upload ".$image);
                }
            }
        }

        $output->writeln("Upload finished.");
    }
}
