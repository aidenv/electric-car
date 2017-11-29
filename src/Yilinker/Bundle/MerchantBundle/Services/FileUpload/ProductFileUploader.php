<?php

namespace Yilinker\Bundle\MerchantBundle\Services\FileUpload;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\AwsS3;
use Symfony\Component\Filesystem\Filesystem as fs;

class ProductFileUploader
{

    const TEMP_FOLDER = 'products/temp/';

    const PRODUCT_FOLDER = 'products/';

    const PRODUCT_FOLDER_SMALL = 'small';

    const PRODUCT_FOLDER_MEDIUM = 'medium';

    const PRODUCT_FOLDER_LARGE = 'large';

    const PRODUCT_FOLDER_THUMBNAIL = 'thumbnail';

    const SIZE_THUMBNAIL_WIDTH = 200;

    const SIZE_THUMBNAIL_HEIGHT = 225;

    const SIZE_SMALL_WIDTH = 200;

    const SIZE_SMALL_HEIGHT = 225;

    const SIZE_MEDIUM_WIDTH = 400;

    const SIZE_MEDIUM_HEIGHT = 451;

    const SIZE_LARGE_WIDTH = 600;

    const SIZE_LARGE_HEIGHT = 677;

    const PRODUCT_DESCRIPTION_IMAGE_FOLDER = 'products/description-image/';

    /**
     * Set File Upload Directory
     * @var string
     **/
    public $uploadDirectory = 'assets/images/uploads';

    /**
     * @var Gaufrette\Filesystem
     */
    private $filesystem;

    /**
     * Environment
     *
     * @var string      
     */
    private $environment;

    private $container = null;

    /**
     * @param Gaufrette\Filesystem $filesystem
     * @param string $environment
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @param string $uploadDirectory
     */
    public function setUploadDirectory($uploadDirectory)
    {
        $this->uploadDirectory = $uploadDirectory;
    }

    public function getUploadDirectory()
    {
        return $this->uploadDirectory;
    }

    /**
     * Upload File
     * @param File $file
     * @param $folderName
     * @param $fileName
     * @return null|string
     */
    public function uploadFile (File $file, $folderName, $fileName)
    {
        $imageLocation = null;
        $fileWithExtension = $fileName . '.' . $file->getClientOriginalExtension();
        $fullPath = $this->uploadDirectory . DIRECTORY_SEPARATOR . $folderName;
        $pathParts = pathinfo($fullPath . DIRECTORY_SEPARATOR . $fileWithExtension);

        if ($file instanceof UploadedFile &&
            $this->moveUploadedFile($file, $fullPath, $pathParts['basename'])) {
            $imageLocation = $fileWithExtension;
        }

        return $imageLocation;
    }

    /**
     * Move Uploaded File to Upload File Directory
     *
     * @param UploadedFile $file
     * @param $uploadDirectory
     * @param $fileWithExtension
     * @return string
     */
    public function moveUploadedFile(UploadedFile $file, $uploadDirectory, $fileWithExtension)
    {
        $isFileCreated = false;
        $file->move($uploadDirectory, $fileWithExtension);
        if (file_exists($uploadDirectory)) {
            $isFileCreated = true;
        }

        return $isFileCreated;
    }

    /**
     * Copy images from temporary folder to specified folder name.
     *
     * @param $fileName
     * @param $folder
     * @return string
     */
    public function moveToPermanentFolder($fileName, $folder)
    {
        $fullPath = $this->uploadDirectory . DIRECTORY_SEPARATOR;
        $productImagePath = $fullPath . self::PRODUCT_FOLDER . $folder . DIRECTORY_SEPARATOR . $fileName;

        if (!file_exists($fullPath . self::PRODUCT_FOLDER . $folder)) {
            mkdir($fullPath . self::PRODUCT_FOLDER . $folder, 0777);
        }

        if (!file_exists($fullPath . self::PRODUCT_FOLDER . $folder . DIRECTORY_SEPARATOR . self::PRODUCT_FOLDER_MEDIUM)) {
            mkdir($fullPath . self::PRODUCT_FOLDER . $folder . DIRECTORY_SEPARATOR . self::PRODUCT_FOLDER_MEDIUM , 0777);
        }

        if (!file_exists($fullPath . self::PRODUCT_FOLDER . $folder . DIRECTORY_SEPARATOR . self::PRODUCT_FOLDER_LARGE)) {
            mkdir($fullPath . self::PRODUCT_FOLDER . $folder . DIRECTORY_SEPARATOR . self::PRODUCT_FOLDER_LARGE , 0777);
        }

        if (!file_exists($fullPath . self::PRODUCT_FOLDER . $folder . DIRECTORY_SEPARATOR . self::PRODUCT_FOLDER_THUMBNAIL)) {
            mkdir($fullPath . self::PRODUCT_FOLDER . $folder . DIRECTORY_SEPARATOR . self::PRODUCT_FOLDER_THUMBNAIL , 0777);
        }

        if (!copy($fullPath . self::TEMP_FOLDER . $fileName, $productImagePath)) {
            $fileName = false;
        }
        else {
            $this->createImageWithDifferentSizes($productImagePath, $folder);
            $this->uploadToCloud(new File($productImagePath));

            $adapter = $this->filesystem->getAdapter();            
            if ($adapter instanceof AwsS3) {
                /**
                 * Remove main directory from local filesystem
                 */
                $mainDirectory = $fullPath . self::PRODUCT_FOLDER . $folder;
                $fs = new fs();
                $fs->remove(array($mainDirectory));                          
            }
            
        }

        return $fileName;
    }

    /**
     * Create Image With Different Sizes
     * Returns the full path of the created images
     *
     * @param string $imageFullPath
     * @param string $folder
     * @return File[]
     */
    public function createImageWithDifferentSizes ($imageFullPath, $folder)
    {
        $createdFiles = array();
        $imageDirectory = $this->uploadDirectory . DIRECTORY_SEPARATOR . self::PRODUCT_FOLDER . $folder;
        $imageSizes = array (
            self::PRODUCT_FOLDER_THUMBNAIL => array (
                'width'=> self::SIZE_THUMBNAIL_WIDTH,
                'height'=> self::SIZE_THUMBNAIL_HEIGHT,
            ),
            self::PRODUCT_FOLDER_SMALL => array (
                'width'=> self::SIZE_SMALL_WIDTH,
                'height'=> self::SIZE_SMALL_HEIGHT,
            ),
            self::PRODUCT_FOLDER_MEDIUM => array (
                'width'=> self::SIZE_MEDIUM_WIDTH,
                'height'=> self::SIZE_MEDIUM_HEIGHT,
            ),
            self::PRODUCT_FOLDER_LARGE => array (
                'width'=> self::SIZE_LARGE_WIDTH,
                'height'=> self::SIZE_LARGE_HEIGHT,
            ),
        );

        $pathParts = pathinfo($imageFullPath);
        $imageManipulator = $this->container->get("yilinker_core.service.image_manipulation");
        
        foreach ($imageSizes as $folderName => $imageSize) {

            if (!file_exists($imageDirectory . DIRECTORY_SEPARATOR . $folderName)) {
                mkdir($imageDirectory . DIRECTORY_SEPARATOR . $folderName , 0777);
            }

            $newWidth = $imageSize['width'];
            $newHeight = $imageSize['height'];
            $imageToReSize = $imageDirectory . DIRECTORY_SEPARATOR . $folderName . DIRECTORY_SEPARATOR . $pathParts['basename'];

            $imageManipulator->writeThumbnail(
                DIRECTORY_SEPARATOR.ltrim($imageFullPath, 'web/'),
                DIRECTORY_SEPARATOR.ltrim($imageToReSize, 'web/'),
                array(
                "filters" => array(
                    "relative_resize" => array(
                        "heighten" => $newHeight,
                        "widen" => $newWidth,
                    ),
                    "background" => array("color" => "#fff")
                )
            ));

            $createdFiles[] = new File($imageToReSize);
        }
        foreach ($createdFiles as $file) {
            $this->uploadToCloud ($file);
        }

        return $createdFiles;
    }

    /**
     * Upload files to the cloud
     *
     * @param $file
     */
    public function uploadToCloud ($file)
    {
        $adapter = $this->filesystem->getAdapter();

        if ($adapter instanceof AwsS3) {
            /**
             * Sanity check to prevent uploads to the web directory. All uploads should point to assets/
             */
            $filepath = ltrim($file->getPathname(), "web/");
            $adapter->setMetadata($filepath, array('contentType' => $file->getMimeType()));
            $adapter->write($filepath, file_get_contents($file->getPathname()));
        }
    }

}
