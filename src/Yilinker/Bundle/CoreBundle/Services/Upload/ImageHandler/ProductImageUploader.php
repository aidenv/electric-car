<?php

namespace Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler\UploaderInterface;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler\Uploader;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageUploader;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;
use Gaufrette\Adapter\AwsS3;
use Carbon\Carbon;

class ProductImageUploader extends Uploader implements UploaderInterface
{
    private $uploadDirectory = null;

    private $fileName;

    public function __construct($forTemp = true){
        $this->forTemp = $forTemp;
    }

    public function createDirectories()
    {
        if($this->forTemp){
            $this->uploadDirectory = "assets/images/uploads/products/temp";
            $this->manageDir("assets/images/uploads/products/", "temp");
        }
        else{
            $id = $this->owner->getProductId();
            $this->uploadDirectory = "assets/images/uploads/products/".$id;
            $this->manageDir("assets/images/uploads/products/", $id);
        }

        return $this;
    }

    public function createImageSizes()
    {
        if(
            !$this->forTemp &&
            file_exists($this->uploadDirectory.DIRECTORY_SEPARATOR.$this->fileName) &&
            !is_dir($this->uploadDirectory.DIRECTORY_SEPARATOR.$this->fileName)
        ){
            $thumbnailDir = $this->uploadDirectory.DIRECTORY_SEPARATOR.ProductImage::PRODUCT_FOLDER_THUMBNAIL.DIRECTORY_SEPARATOR.$this->fileName;
            $smallDir = $this->uploadDirectory.DIRECTORY_SEPARATOR.ProductImage::PRODUCT_FOLDER_SMALL.DIRECTORY_SEPARATOR.$this->fileName;
            $mediumDir = $this->uploadDirectory.DIRECTORY_SEPARATOR.ProductImage::PRODUCT_FOLDER_MEDIUM.DIRECTORY_SEPARATOR.$this->fileName;
            $largeDir = $this->uploadDirectory.DIRECTORY_SEPARATOR.ProductImage::PRODUCT_FOLDER_LARGE.DIRECTORY_SEPARATOR.$this->fileName;

            $settings = array(
                array(
                    "uploadDirectory" => $thumbnailDir,
                    "resizeWidth" => ProductImage::SIZE_THUMBNAIL_WIDTH,
                    "resizeHeight" => ProductImage::SIZE_THUMBNAIL_HEIGHT
                ),
                array(
                    "uploadDirectory" => $smallDir,
                    "resizeWidth" => ProductImage::SIZE_SMALL_WIDTH,
                    "resizeHeight" => ProductImage::SIZE_SMALL_HEIGHT
                ),
                array(
                    "uploadDirectory" => $mediumDir,
                    "resizeWidth" => ProductImage::SIZE_MEDIUM_WIDTH,
                    "resizeHeight" => ProductImage::SIZE_MEDIUM_HEIGHT
                ),
                array(
                    "uploadDirectory" => $largeDir,
                    "resizeWidth" => ProductImage::SIZE_LARGE_WIDTH,
                    "resizeHeight" => ProductImage::SIZE_LARGE_HEIGHT
                )
            );

            foreach ($settings as $setting){

                $this->manipulateImage(
                    $setting["uploadDirectory"],
                    $setting["resizeWidth"],
                    $setting["resizeHeight"]
                );

                $adapter = $this->fileSystem->getAdapter();

                if($adapter instanceof AwsS3){
                    $this->uploadToCloud($setting["uploadDirectory"], $this->image->getMimeType());
                }
            }
        }

        return $this;
    }

    public function upload()
    {
        if($this->forTemp){
            $imageName = trim($this->owner->getUserId()."_".rand(1,100)."_".time());

            $this->fileName = $imageName.".".$this->image->getClientOriginalExtension();


            if(
                $this->image instanceof UploadedFile &&
                $this->moveUploadedFile($this->image, $this->uploadDirectory, $this->fileName)
            ){
                return $this;
            }
        }else{
            $imageName = $this->image->getImageLocation(true);
            $this->entity = $this->image;

            $path = "assets/images/uploads/".ProductImage::PRODUCT_FOLDER."temp/".$imageName;

            if(file_exists($path)){
                $this->image = new File($path);
                $this->fileName = $imageName;

                if(
                    $this->image instanceof File &&
                    $this->moveUploadedFile($this->image, $this->uploadDirectory, $this->fileName)
                ){
                    return $this;
                }
            }
        }

        return $this;
    }

    public function moveUploadedFile($file, $uploadDirectory, $fileWithExtension)
    {
        $this->image = $movedFile = $file->move($this->uploadDirectory, $fileWithExtension);

        $adapter = $this->fileSystem->getAdapter();

        if($adapter instanceof AwsS3 && !$this->forTemp){
            $this->uploadToCloud($movedFile->getPathname(), $movedFile->getMimeType());
        }

        return $fileWithExtension;
    }

    public function uploadToCloud($pathName, $mimeType)
    {
        $adapter = $this->fileSystem->getAdapter();
        $adapter->setMetadata(
            $pathName,
            array(
                "contentType" => $mimeType
            )
        );

        $adapter->write($pathName, file_get_contents($pathName));
    }

    public function manageDir($mainDir, $folder)
    {
        if(!file_exists($mainDir)){
            mkdir($mainDir, 0777);
        }

        if(!file_exists($mainDir.$folder)){
            mkdir($mainDir.$folder, 0777);
        }

        if(!$this->forTemp){
            if(!file_exists($mainDir.$folder.DIRECTORY_SEPARATOR."large")){
                mkdir($mainDir.$folder.DIRECTORY_SEPARATOR."large", 0777);
            }

            if(!file_exists($mainDir.$folder.DIRECTORY_SEPARATOR."medium")){
                mkdir($mainDir.$folder.DIRECTORY_SEPARATOR."medium", 0777);
            }

            if(!file_exists($mainDir.$folder.DIRECTORY_SEPARATOR."small")){
                mkdir($mainDir.$folder.DIRECTORY_SEPARATOR."small", 0777);
            }

            if(!file_exists($mainDir.$folder.DIRECTORY_SEPARATOR."thumbnail")){
                mkdir($mainDir.$folder.DIRECTORY_SEPARATOR."thumbnail", 0777);
            }
        }
    }

    public function manipulateImage($uploadDirectory, $resizeWidth, $resizeHeight)
    {
        $this->manipulator->writeThumbnail(
            $this->uploadDirectory.DIRECTORY_SEPARATOR.$this->fileName,
            $uploadDirectory,
            array(
            "filters" => array(
                "relative_resize" => array(
                    "widen" => $resizeWidth,
                    "heighten" => $resizeHeight
                ),
            ),
        ));
    }

    public function getEntity()
    {
        if($this->forTemp){
            return array(
                "fileName" => $this->fileName
            );
        }
        else{
            return array(
                "productImageId" => $this->entity->getProductImageId(),
                "fileName" => $this->entity->getImageLocation(true),
                "raw" => $this->assetsHelper->getUrl($this->entity->getImageLocation(), "product"),
                "thumbnail" => $this->assetsHelper->getUrl($this->entity->getImageLocationBySize("thumbnail"), "product"),
                "small" => $this->assetsHelper->getUrl($this->entity->getImageLocationBySize("small"), "product"),
                "medium" => $this->assetsHelper->getUrl($this->entity->getImageLocationBySize("medium"), "product"),
                "large" => $this->assetsHelper->getUrl($this->entity->getImageLocationBySize("large"), "product")
            );
        }
    }
}
