<?php

namespace Yilinker\Bundle\CoreBundle\Services\Upload;

use Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler\UserPhotoUploader;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler\LegalDocumentUploader;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler\ProductImageUploader;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Yilinker\Bundle\CoreBundle\Entity\LegalDocument;
use Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType;

use Carbon\Carbon;

class ImageUploader
{
    const UPLOAD_TYPE_PROFILE_PHOTO = "profile";

    const UPLOAD_TYPE_COVER_PHOTO = "cover";

    const UPLOAD_TYPE_TIN = "tin";

    const UPLOAD_TYPE_VALID_ID = "valid_id";

    const UPLOAD_TYPE_PRODUCT = "product";

    private $fileSystem;

    private $imageManipulator;

    private $em;

    private $assetsHelper;

    public function __construct($em, $fileSystem, $imageManipulator, $assetsHelper)
    {
        $this->em = $em;
        $this->fileSystem = $fileSystem;
        $this->imageManipulator = $imageManipulator;
        $this->assetsHelper = $assetsHelper;
    }

    public function upload($owner, $type, $image)
    {
        $uploader = null;
        switch ($type) {
            case self::UPLOAD_TYPE_PROFILE_PHOTO:
            case self::UPLOAD_TYPE_COVER_PHOTO:
                $uploader = new UserPhotoUploader();
                break;
            case self::UPLOAD_TYPE_VALID_ID:
                $uploader = new LegalDocumentUploader();
                break;
            case self::UPLOAD_TYPE_PRODUCT:
                $uploader = new ProductImageUploader();
        }

        if(!is_null($uploader)){

            try{

                $uploader->setImage($image)
                         ->setType($type)
                         ->setOwner($owner)
                         ->setEntityManager($this->em)
                         ->setFileSystem($this->fileSystem)
                         ->setManipulator($this->imageManipulator)
                         ->setAssetsHelper($this->assetsHelper)
                         ->createDirectories()
                         ->upload()
                         ->createImageSizes();

                return $uploader->getEntity();
            }
            catch(YilinkerException $e){
                return null;
            }
        }

        return null;
    }

    public function uploadLegalDoc($fileName, $user, $type)
    {
        $uploader = new LegalDocumentUploader(false);
        $uploader->setImage($fileName)
                 ->setType($type)
                 ->setOwner($user)
                 ->setEntityManager($this->em)
                 ->setFileSystem($this->fileSystem)
                 ->setManipulator($this->imageManipulator)
                 ->setAssetsHelper($this->assetsHelper)
                 ->createDirectories()
                 ->upload();

        if($uploader->getIdByString() == LegalDocumentType::TYPE_VALID_ID){
             $accreditationApplication = $user->getAccreditationApplication();
            $accreditationApplication->setIsBusinessEditable(false);

            $this->em->flush();
        }
    }

    public function uploadProductImage($file, $product, $type)
    {
        $uploader = new ProductImageUploader(false);
        $uploader->setImage($file)
                 ->setType($type)
                 ->setOwner($product)
                 ->setEntityManager($this->em)
                 ->setFileSystem($this->fileSystem)
                 ->setManipulator($this->imageManipulator)
                 ->setAssetsHelper($this->assetsHelper)
                 ->createDirectories()
                 ->upload()
                 ->createImageSizes();
    }

    public function uploadProductImages($product)
    {
        $images = $product->getImages(false, true);
        foreach($images as $image){
            $this->uploadProductImage(
                $image,
                $product,
                self::UPLOAD_TYPE_PRODUCT
            );
        }
    }
}
