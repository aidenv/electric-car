<?php

namespace Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler\UploaderInterface;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler\Uploader;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageUploader;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Gaufrette\Adapter\AwsS3;
use Carbon\Carbon;

class UserPhotoUploader extends Uploader implements UploaderInterface
{
    private $uploadDirectory = null;

    private $fileName;

    public function createDirectories()
    {
        $id = $this->owner->getUserId();
        $this->uploadDirectory = "assets/images/uploads/users/".$id;
        $this->manageDir("assets/images/uploads/users/", $id);

        return $this;
    }

    public function createImageSizes()
    {
        $thumbnailDir = $this->uploadDirectory.DIRECTORY_SEPARATOR.UserImage::IMAGE_SIZE_THUMBNAIL.DIRECTORY_SEPARATOR.$this->fileName;
        $smallDir = $this->uploadDirectory.DIRECTORY_SEPARATOR.UserImage::IMAGE_SIZE_SMALL.DIRECTORY_SEPARATOR.$this->fileName;
        $mediumDir = $this->uploadDirectory.DIRECTORY_SEPARATOR.UserImage::IMAGE_SIZE_MEDIUM.DIRECTORY_SEPARATOR.$this->fileName;
        $largeDir = $this->uploadDirectory.DIRECTORY_SEPARATOR.UserImage::IMAGE_SIZE_LARGE.DIRECTORY_SEPARATOR.$this->fileName;

        $settings = array(
            array(
                "uploadDirectory" => $thumbnailDir,
                "resizeWidth" => UserImage::AVATAR_SIZE_THUMBNAIL_WIDTH,
                "resizeHeight" => UserImage::AVATAR_SIZE_THUMBNAIL_HEIGHT
            ),
            array(
                "uploadDirectory" => $smallDir,
                "resizeWidth" => UserImage::AVATAR_SIZE_SMALL_WIDTH,
                "resizeHeight" => UserImage::AVATAR_SIZE_SMALL_HEIGHT
            ),
            array(
                "uploadDirectory" => $mediumDir,
                "resizeWidth" => UserImage::AVATAR_SIZE_MEDIUM_WIDTH,
                "resizeHeight" => UserImage::AVATAR_SIZE_MEDIUM_HEIGHT
            ),
            array(
                "uploadDirectory" => $largeDir,
                "resizeWidth" => UserImage::AVATAR_SIZE_LARGE_WIDTH,
                "resizeHeight" => UserImage::AVATAR_SIZE_LARGE_HEIGHT
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

        return $this;
    }

    public function upload()
    {
        $imageName = sha1(uniqid().time().uniqid());

        $this->fileName = $imageName.".".$this->image->getClientOriginalExtension();

        if(
            $this->image instanceof UploadedFile && 
            $this->moveUploadedFile($this->image, $this->uploadDirectory, $this->fileName)
        ){
            $userImage = new UserImage();
            $userImage->setImageLocation($this->fileName)
                      ->setUser($this->owner)
                      ->setIsHidden(false)
                      ->setDateAdded(Carbon::now())
                      ->setUserImageType(
                            $this->type == ImageUploader::UPLOAD_TYPE_PROFILE_PHOTO ?
                                UserImage::IMAGE_TYPE_AVATAR : UserImage::IMAGE_TYPE_BANNER
                        );

            $this->em->persist($userImage);
            $this->em->flush();

            $this->entity = $userImage;

            return $this;
        }

        return null;
    }

    public function moveUploadedFile($file, $uploadDirectory, $fileWithExtension)
    {
        $this->image = $movedFile = $file->move($this->uploadDirectory, $fileWithExtension);

        $adapter = $this->fileSystem->getAdapter();

        if($adapter instanceof AwsS3){
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
        return array(
            "userImageId" => $this->entity->getUserImageId(),
            "fileName" => $this->entity->getImageLocation(true),
            "raw" => $this->assetsHelper->getUrl($this->entity->getImageLocation(), "user"),
            "thumbnail" => $this->assetsHelper->getUrl($this->entity->getImageLocationBySize("thumbnail"), "user"),
            "small" => $this->assetsHelper->getUrl($this->entity->getImageLocationBySize("small"), "user"),
            "medium" => $this->assetsHelper->getUrl($this->entity->getImageLocationBySize("medium"), "user"),
            "large" => $this->assetsHelper->getUrl($this->entity->getImageLocationBySize("large"), "user")
        );
    }

    private function getLength($isWidth = true, $size)
    {
        if($this->type == ImageUploader::UPLOAD_TYPE_PROFILE_PHOTO){
            switch($size){
                case UserImage::IMAGE_SIZE_THUMBNAIL:
                    return $isWidth? UserImage::AVATAR_SIZE_THUMBNAIL_WIDTH : UserImage::AVATAR_SIZE_THUMBNAIL_HEIGHT;
                case UserImage::IMAGE_SIZE_SMALL:
                    return $isWidth? UserImage::AVATAR_SIZE_SMALL_WIDTH : UserImage::AVATAR_SIZE_SMALL_HEIGHT;
                case UserImage::IMAGE_SIZE_MEDIUM:
                    return $isWidth? UserImage::AVATAR_SIZE_MEDIUM_WIDTH : UserImage::AVATAR_SIZE_MEDIUM_HEIGHT;
                case UserImage::IMAGE_SIZE_LARGE:
                    return $isWidth? UserImage::AVATAR_SIZE_LARGE_WIDTH : UserImage::AVATAR_SIZE_LARGE_HEIGHT;
            }
        }
        elseif($this->type == ImageUploader::UPLOAD_TYPE_COVER_PHOTO){
            switch($size){
                case UserImage::IMAGE_SIZE_THUMBNAIL:
                    return $isWidth? UserImage::COVER_SIZE_THUMBNAIL_WIDTH : UserImage::COVER_SIZE_THUMBNAIL_HEIGHT;
                case UserImage::IMAGE_SIZE_SMALL:
                    return $isWidth? UserImage::COVER_SIZE_SMALL_WIDTH : UserImage::COVER_SIZE_SMALL_HEIGHT;
                case UserImage::IMAGE_SIZE_MEDIUM:
                    return $isWidth? UserImage::COVER_SIZE_MEDIUM_WIDTH : UserImage::COVER_SIZE_MEDIUM_HEIGHT;
                case UserImage::IMAGE_SIZE_LARGE:
                    return $isWidth? UserImage::COVER_SIZE_LARGE_WIDTH : UserImage::COVER_SIZE_LARGE_HEIGHT;
            }
        }
    }
}