<?php

namespace Yilinker\Bundle\CoreBundle\Services\ManufacturerProduct;

use Yilinker\Bundle\CoreBundle\Entity\Brand;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;
use Gaufrette\Adapter\AwsS3;
use Symfony\Component\HttpFoundation\File\File;

class ManufacturerProductService
{
    private $assetsHelper;
    private $container = null;

    public function __construct(AssetsHelper $assetsHelper)
    {
        $this->assetsHelper = $assetsHelper;
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function constructProductsData($manufacturerProducts)
    {
        $products = array();

        foreach($manufacturerProducts as $manufacturerProduct){
            if($manufacturerProduct->getDefaultUnit() || $manufacturerProduct->getFirstUnit()){
                array_push($products, $this->constructManufacturerProduct($manufacturerProduct));
            }
        }

        return $products;
    }

    public function constructManufacturerProduct($manufacturerProduct)
    {
        $manufacturerProductDetails = array();
        $productUnits = array();
        $productImages = array();
        $manufacturerProductUnits = $manufacturerProduct->getUnits();
        $images = $manufacturerProduct->getImages();

        foreach($manufacturerProductUnits as $manufacturerProductUnit){
            $combinations = $manufacturerProductUnit->getCombination();

            array_push($productUnits, array(
                "manufacturerProductUnitId" => $manufacturerProductUnit->getManufacturerProductUnitId(),
                "sku"                       => $manufacturerProductUnit->getSku(),
                "quantity"                  => $manufacturerProductUnit->getQuantity(),
                "length"                    => $manufacturerProductUnit->getLength(),
                "width"                     => $manufacturerProductUnit->getWidth(),
                "height"                    => $manufacturerProductUnit->getHeight(),
                "weight"                    => $manufacturerProductUnit->getWeight(),
                "price"                     => $manufacturerProductUnit->getPrice(),
                "discountedPrice"           => $manufacturerProductUnit->getDiscountedPrice(),
                "shippingFee"               => $manufacturerProductUnit->getShippingFee(),
                "retailPrice"               => $manufacturerProductUnit->getRetailPrice(),
                "commission"                => $manufacturerProductUnit->getCommission(),
                "combinations"              => $combinations,
            ));
        }

        foreach ($images as $image) {
            array_push($productImages, $this->assetsHelper->getUrl($image->getImageLocation(), "manufacturer_product"));
        }

        $brandName = null;
        if($manufacturerProduct->getBrand() && $manufacturerProduct->getBrand()->getBrandId() !== Brand::CUSTOM_BRAND_ID){
            $brandName = $manufacturerProduct->getBrand()->getName();
        }
        

        $manufacturerProductDetails = array(
            "manufacturerProductId" => $manufacturerProduct->getManufacturerProductId(),
            "dateAdded"             => $manufacturerProduct->getDateAdded()->format("Y-m-d H:i:s"),
            "name"                  => $manufacturerProduct->getName(),
            "storeName"             => $manufacturerProduct->getManufacturer()->getName(),
            "category"              => $manufacturerProduct->getProductCategory() ? $manufacturerProduct->getProductCategory()->getName(): '',
            "brand"                 => $brandName,
            "sku"                   => $manufacturerProduct->getDefaultUnit()? $manufacturerProduct->getDefaultUnit()->getSku() : $manufacturerProduct->getFirstUnit()->getSku(),
            "description"           => $manufacturerProduct->getDescription(),
            "shortDescription"      => $manufacturerProduct->getShortDescription(),
            "condition"             => $manufacturerProduct->getCondition() ? $manufacturerProduct->getCondition()->getName() : null,
            "originalPrice"         => $manufacturerProduct->getDefaultUnit()? $manufacturerProduct->getDefaultUnit()->getPrice() : $manufacturerProduct->getFirstUnit()->getPrice(),
            "discountedPrice"       => $manufacturerProduct->getDefaultUnit()? $manufacturerProduct->getDefaultUnit()->getDiscountedPrice() : $manufacturerProduct->getFirstUnit()->getDiscountedPrice(),
            "commission"            => $manufacturerProduct->getDefaultUnit()? $manufacturerProduct->getDefaultUnit()->getCommission() : $manufacturerProduct->getFirstUnit()->getCommission(),
            "discount"              => $manufacturerProduct->getDefaultUnit()? $manufacturerProduct->getDefaultUnit()->getDiscountPercentage() : $manufacturerProduct->getFirstUnit()->getDiscountPercentage(),
            "length"                => $manufacturerProduct->getDefaultUnit()? $manufacturerProduct->getDefaultUnit()->getLength() : $manufacturerProduct->getFirstUnit()->getLength(),
            "width"                 => $manufacturerProduct->getDefaultUnit()? $manufacturerProduct->getDefaultUnit()->getWidth() : $manufacturerProduct->getFirstUnit()->getWidth(),
            "height"                => $manufacturerProduct->getDefaultUnit()? $manufacturerProduct->getDefaultUnit()->getHeight() : $manufacturerProduct->getFirstUnit()->getHeight(),
            "weight"                => $manufacturerProduct->getDefaultUnit()? $manufacturerProduct->getDefaultUnit()->getWeight() : $manufacturerProduct->getFirstUnit()->getWeight(),
            "status"                => $manufacturerProduct->getStatus(),
            "units"                 => $productUnits,
            "images"                => $productImages,
        );

        return $manufacturerProductDetails;
    }

    public function syncImages($inhouseProduct, $photoImages)
    {
        if (!$inhouseProduct) {
            return;
        }

        $em = $this->container->get('doctrine.orm.entity_manager');
        $kernel = $this->container->get('kernel');
        $imageDir = $inhouseProduct->imageDir();
        $dir = $kernel->getRootDir().'/../../web/'.$imageDir;

        // set isDelete true to database images not on photo images
        $images = $inhouseProduct->getImages();
        $dbImages = array();
        foreach ($images as $image) {
            $imgsrc = $image->getRawImageLocation();
            if (!in_array($imgsrc, $photoImages)) {
                $image->setIsDeleted(true);
            }
            $dbImages[] = $imgsrc;
        }

        // create new database entry for new images
        $newImages = array_diff($photoImages, $dbImages);
        foreach ($newImages as $newImage) {
            if (!is_file($dir.DIRECTORY_SEPARATOR.$newImage)) {
                continue;
            }

            $productImage = new ProductImage;
            $productImage->setImageLocation($newImage);
            $productImage->setProduct($inhouseProduct);
            $em->persist($productImage);

            $fs = $this->container->get('photo_storage_filesystem');
            $adapter = $fs->getAdapter();

            if ($adapter instanceof AwsS3) {
                $file = new File($dir.DIRECTORY_SEPARATOR.$newImage, false);
                $imageAssetPath = $imageDir.DIRECTORY_SEPARATOR.$newImage;
                $adapter->setMetadata($file->getPathname(), array('contentType' => $file->getMimeType()));
                $adapter->write($imageAssetPath, file_get_contents($file->getPathname()));
            }

            $this->createFolder($inhouseProduct->getProductId(),$newImage);
        }

        // delete new images that were removed from the list
        $validImages = array_unique(array_merge($dbImages, $newImages));
        if (!is_dir($dir)) {
            @mkdir($dir, 0777);
        }
        $scannedDir = scandir($dir);
        foreach ($scannedDir as $scanned) {
            if (is_dir($scanned)) continue;
            //if (!in_array($scanned, $validImages)) unlink($dir.DIRECTORY_SEPARATOR.$scanned);
        }

        $em->flush();
    }


    public function createFolder($productId,$imageLocation)
    {
        $fileUploadService = $this->container->get('yilinker_backend.service.product_file_uploader');

        $folderName = $fileUploadService::PRODUCT_FOLDER . $productId;
        $imageFullPath = $fileUploadService->getUploadDirectory() . DIRECTORY_SEPARATOR . $folderName . DIRECTORY_SEPARATOR . $imageLocation;
        $fileUploadService->createImageWithDifferentSizes ($imageFullPath, $productId);
        $fileUploadService->uploadToCloud (new File($imageFullPath));
    }
}
