<?php

namespace Yilinker\Bundle\MerchantBundle\Services\Reseller;

use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\Country;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductMap;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnitMap;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeName;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeValue;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\ProductAttributeName;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductAttributeValue;
use Yilinker\Bundle\CoreBundle\Entity\ProductCountry;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\AwsS3;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Yaml\Parser;
use DateTime;
use Symfony\Component\Filesystem\Filesystem as fs;

class ResellerUploader
{
    const MANUFACTURER_PRODUCT_IMAGE_DIRECTORY = 'assets/images/uploads/manufacturer_products';

    const PRODUCT_IMAGE_DIRECTORY = 'assets/images/uploads/products';

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * Reseller config parameter bag
     *
     * @var Symfony\Component\HttpFoundation\ParameterBag $config
     */
    private $resellerConfig;

    /**
     * Product config parameter bag
     *
     * @var Symfony\Component\HttpFoundation\ParameterBag $config
     */
    private $productConfig;


    /**
     * Tax percentage
     *
     * @var string $taxPercentage
     */
    private $taxPercentage;

    /**
     * Affliate percentage commision multiplier
     *
     * @var string $comissionMultiplerPercentage
     */
    private $comissionMultiplerPercentage;

    /**
     * Gaufrette Filesystem
     *
     * @var Gaufrette\Filesystem
     */
    private $filesystem;

    /**
     * File Uploader
     *
     * @var Yilinker\Bundle\MerchantBundle\Services\FileUpload\ProductFileUploader
     */
    private $fileUploader;

    private $yilinkerTranslatable;

    /**
     * Asset hostname
     *
     * @var string
     */
    private $assetHostname;

    /**
     * Constructor
     *
     * @param Doctrine\ORM\EntityManager $entityManager
     * @param Gaufrette\Filesystem $filesystem
     * @param string $taxPercentage
     * @param string $commissionMultiplierPercentage
     * @param string $resellerConfigPath
     * @param Yilinker\Bundle\MerchantBundle\Services\FileUpload\ProductFileUploader $fileUploader
     * @param string $assetHostname
     */
    public function __construct(
        EntityManager $entityManager,
        $filesystem,
        $taxPercentage = "12",
        $comissionMultiplerPercentage = "60",
        $resellerConfigPath = null,
        $fileUploader,
        $yilinkerTranslatable,
        $assetHostname
    )
    {
        $this->filesystem = $filesystem;
        $this->entityManager = $entityManager;
        $this->taxPercentage = $taxPercentage;
        $this->comissionMultiplerPercentage = $comissionMultiplerPercentage;
        $this->setConfig($resellerConfigPath);
        $this->fileUploader = $fileUploader;
        $this->yilinkerTranslatable = $yilinkerTranslatable;
        $this->assetHostname = $assetHostname;
    }

    /**
     * Set the reseller configuration
     *
     * @param $string $path
     */
    public function setConfig($resellerConfigPath = null)
    {
        $yaml = new Parser;
        $path = $resellerConfigPath ? $resellerConfigPath : '/../../Resources/config/reseller.yml';
        $config = $yaml->parse(file_get_contents(__DIR__.$path));
        $this->resellerConfig = new ParameterBag($config);

        $productMetaData = $this->entityManager->getClassMetadata('YilinkerCoreBundle:Product');
        $yaml = new Parser;
        $config = $productMetaData->getFieldNames();
        $associations = $productMetaData->getassociationMappings();
        foreach($associations as $key => $associattion){
            $config[] = $key;
        }
        $this->productConfig = new ParameterBag($config);
    }

    /**
     * Get reseller products editable fields
     *
     * @return mixed
     */
    public function getProductEditableFields()
    {
        $tables = $this->resellerConfig->get('tables');
        $editableFields = array();
        foreach($tables['Product']['fields'] as $key => $productConfig){
            if(isset($productConfig['editable']) && $productConfig['editable']){
                $editableFields[$key] = array(
                    'field' => $key,
                    'label' => $productConfig['label'],
                );
            }
        }

        return $editableFields;
    }

    /**
     * Get reseller products non-editable fields
     *
     * @return mixed
     */
    public function getProductNonEditableFields()
    {
        $editableFields = $this->getProductEditableFields();

        return array_diff($this->productConfig->all(), array_keys($editableFields));
    }

    /**
     * Upload a ManufacturerProduct as an actual product
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $uploader
     * @param Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct $manufacturerProduct
     * @return array
     */
    public function uploadProduct($uploader, $manufacturerProduct, $country = "ph")
    {
        $response = array(
            'error' => '',
            'isSuccessful' => false,
            'product' => null,
        );

        if(!$country instanceof Country){
            $country = $this->entityManager
                ->getRepository("YilinkerCoreBundle:Country")
                ->findOneByCode($country);
        }

        if($uploader === null || $uploader->getUserType() !== User::USER_TYPE_SELLER || $uploader->getStore()->getStoreType() != Store::STORE_TYPE_RESELLER){
            $response['error'] = "This user is not allowed to resell a product";
        }
        else if($manufacturerProduct === null || $manufacturerProduct->getStatus() !== ManufacturerProduct::STATUS_ACTIVE){
            $response['error'] = "Manufacturer product does not exist";
        }
        else{
            $existingProductMaps = $this->entityManager->getRepository('YilinkerCoreBundle:ManufacturerProduct')
                                        ->getManufacturerProductsByUser(
                                            $uploader,
                                            $manufacturerProduct->getManufacturerProductId(),
                                            Product::FULL_DELETE,
                                            null,
                                            $country
                                        );

            if(count($existingProductMaps) > 0){
                $response['error'] = "Manufacturer product has already been selected by this reseller.";
            }
            else{

                $truncatedShortDescription = substr($manufacturerProduct->getShortDescription(), 0, (ManufacturerProduct::SHORT_DESCRIPTION_LENGTH - 1));
                $currentDatetime = new DateTime();
                $primaryImage = $manufacturerProduct->getPrimaryImage();

                $product = new Product();
                $product->setUser($uploader);
                $product->setDateCreated($currentDatetime);
                $product->setDateLastModified($currentDatetime);
                $product->setClickCount(0);
                $product->setName($manufacturerProduct->getName());
                $product->setDescription($manufacturerProduct->getDescription());
                $product->setShortDescription($truncatedShortDescription);
                $product->setIsCod($manufacturerProduct->getIsCod());
                $product->setCondition($manufacturerProduct->getCondition());
                $product->setProductCategory($manufacturerProduct->getProductCategory());
                $product->setBrand($manufacturerProduct->getBrand());
                $product->setStatus(Product::ACTIVE);
                $product->setKeywords($manufacturerProduct->getKeywords());

                $productCountry = new ProductCountry;
                $productCountry->setProduct($product)
                               ->setCountry($country)
                               ->setStatus(Product::ACTIVE);

                $this->entityManager->persist($productCountry);

                $defaultLocale = $this->getProductLocale($manufacturerProduct);

                $product->setDefaultLocale($defaultLocale);

                $imagesUploads = array();
                foreach($manufacturerProduct->getImages() as $manufacturerProductImage){
                    $isPrimary = $primaryImage && $primaryImage->getManufacturerProductImageId() === $manufacturerProductImage->getManufacturerProductImageId();
                    if($manufacturerProductImage->getIsDelete()){
                        continue;
                    }
                    $productImage = new ProductImage();
                    $productImage->setProduct($product);
                    $productImage->setImageLocation($manufacturerProductImage->getRawImageLocation());
                    $productImage->setIsDeleted(false);
                    $productImage->setIsPrimary($isPrimary)
                                 ->setDefaultLocale($defaultLocale);
                    $this->entityManager->persist($productImage);
                    $imagesUploads[] = array(
                        'source' => $manufacturerProductImage->getImageLocation(),
                        'destination' => $productImage->getRawImageLocation(),
                    );
                }

                $manufacturerProductAttributeUnitMap = array();
                $productUnits = array();

                foreach($manufacturerProduct->getRetailPriceSetUnits() as $key => $manufacturerProductUnit){
                    $productUnit = new ProductUnit();
                    $productUnit->setProduct($product);
                    $productUnit->setSku($manufacturerProductUnit->getSku());
                    $productUnit->setDateCreated($currentDatetime);
                    $productUnit->setDateLastModified($currentDatetime);
                    $productUnit->setPrice($manufacturerProductUnit->getPrice());
                    $productUnit->setDiscountedPrice($manufacturerProductUnit->getRetailPrice());
                    $productUnit->setWidth($manufacturerProductUnit->getWidth());
                    $productUnit->setHeight($manufacturerProductUnit->getHeight());
                    $productUnit->setLength($manufacturerProductUnit->getLength());
                    $productUnit->setWeight($manufacturerProductUnit->getWeight());

                    $productUnit->setStatus(
                        $manufacturerProductUnit->getStatus() == ManufacturerProductUnit::MANUFACTURER_PRODUCT_STATUS_ACTIVE?
                            ProductUnit::STATUS_ACTIVE : ProductUnit::STATUS_INACTIVE
                    );

                    $this->entityManager->persist($productUnit);

                    $manufacturerProductUnitMap = new ManufacturerProductUnitMap();
                    $manufacturerProductUnitMap->setProductUnit($productUnit);
                    $manufacturerProductUnitMap->setManufacturerProductUnit($manufacturerProductUnit);
                    $this->entityManager->persist($manufacturerProductUnitMap);
                    /**
                     * Create mapping for product unit to manufacturer product attribute value
                     */
                    $productUnits[$key] = $productUnit;
                    foreach($manufacturerProductUnit->getManufacturerProductAttributeValues() as $attributeValue){
                        $manufacturerProductAttributeUnitMap[$attributeValue->getManufacturerProductAttributeValueId()] = $key;
                    }
                }

                foreach($manufacturerProduct->getAvailableAttributes() as $attribute){
                    $productAttributeName = new ProductAttributeName();
                    $productAttributeName->setName($attribute['groupName']);
                    $productAttributeName->setProduct($product);
                    $this->entityManager->persist($productAttributeName);
                    foreach($attribute['items'] as $attributeValue){
                        if (!array_key_exists($attributeValue['id'], $manufacturerProductAttributeUnitMap)) {
                            continue;
                        }
                        if (!array_key_exists($manufacturerProductAttributeUnitMap[$attributeValue['id']], $productUnits)) {
                            continue;
                        }
                        $productUnit = $productUnits[$manufacturerProductAttributeUnitMap[$attributeValue['id']]];
                        $productAttributeValue = new ProductAttributeValue();
                        $productAttributeValue->setProductUnit($productUnit);
                        $productAttributeValue->setProductAttributeName($productAttributeName);
                        $productAttributeValue->setValue($attributeValue['name']);
                        $this->entityManager->persist($productAttributeValue);
                    }
                }

                $manufacturerProductMap = new ManufacturerProductMap();
                $manufacturerProductMap->setProduct($product);
                $manufacturerProductMap->setManufacturerProduct($manufacturerProduct);
                $product->setManufacturerProductMap($manufacturerProductMap);

                $this->entityManager->persist($product);
                $this->entityManager->persist($manufacturerProductMap);
                // $this->addProductCountry($product, $manufacturerProduct);

                try{
                    $this->entityManager->flush();
                    $response['product'] = $product;
                    $response['isSuccessful'] = true;
                    $productImageDirectory = self::PRODUCT_IMAGE_DIRECTORY.DIRECTORY_SEPARATOR.$product->getProductId();
                    if (!file_exists($productImageDirectory)) {
                        mkdir($productImageDirectory , 0777);
                    }
                    $adapter = $this->filesystem->getAdapter();
                    foreach($imagesUploads as $imageUpload){

                        $destinationImage = $productImageDirectory.DIRECTORY_SEPARATOR.$imageUpload['destination'];
                        $sourceImage = self::MANUFACTURER_PRODUCT_IMAGE_DIRECTORY.DIRECTORY_SEPARATOR.$imageUpload['source'];
                        if($adapter instanceof AwsS3){
                            $content = file_get_contents(rtrim($this->assetHostname, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR. urlencode($sourceImage));
                            file_put_contents($destinationImage, $content);
                        }
                        else{
                            copy($sourceImage, $destinationImage);
                        }

                        $this->fileUploader->createImageWithDifferentSizes ($destinationImage, $product->getProductId());
                        /**
                         * Upload main image file to the cloud
                         */
                        if($adapter instanceof AwsS3){
                            $file = new File($destinationImage);
                            $adapter->setMetadata($file->getPathname(), array('contentType' => $file->getMimeType()));
                            $adapter->write($file->getPathname(), file_get_contents($file->getPathname()));
                        }
                    }

                    if($adapter instanceof AwsS3){
                        /**
                         * Remove main directory from local filesystem
                         */
                        $fs = new fs();
                        $fs->remove(array($productImageDirectory));
                    }

                }
                catch(\Exception $e){
                    $response['error'] = 'Product cannot be uploaded at this time';
                }
            }
        }

        return $response;
    }

    /**
     * Sync product with its correspodnign manufacturer product
     * [Warning: Syncing the images takes a lot of resources. Use with caution]
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @param string $uploadDirectory
     * @param boolean $syncImages
     */
    public function syncProduct(Product $product, $uploadDirectory, $syncImages = false)
    {
        $manufacturerProductUnitMapRepo = $this->entityManager->getRepository('YilinkerCoreBundle:ManufacturerProductUnitMap');
        $productImageRepo = $this->entityManager->getRepository('YilinkerCoreBundle:ProductImage');
        $productAttributeNameRepo = $this->entityManager->getRepository('YilinkerCoreBundle:ProductAttributeName');
        $productAttributeValueRepo = $this->entityManager->getRepository('YilinkerCoreBundle:ProductAttributeValue');

        $adapter = $this->filesystem->getAdapter();
        $productImageDirectory = 'web/'.self::PRODUCT_IMAGE_DIRECTORY.DIRECTORY_SEPARATOR.$product->getProductId();
        $currentDatetime = new DateTime();

        $this->fileUploader->setUploadDirectory('web/'.$uploadDirectory);

        $manufacturerProductMap = $this->entityManager->getRepository('YilinkerCoreBundle:ManufacturerProductMap')
                                                      ->findOneByProduct($product);

        if ($manufacturerProductMap) {
            $manufacturerProduct = $manufacturerProductMap->getManufacturerProduct();

            try{
                $statusArray = array(
                    ManufacturerProduct::STATUS_INACTIVE => Product::INACTIVE,
                    ManufacturerProduct::STATUS_DELETED  => Product::DELETE,
                );
                $productStatus = null;
                if (isset($statusArray[$manufacturerProduct->getStatus()])) {
                    $productStatus = $statusArray[$manufacturerProduct->getStatus()];
                }

                // updating product
                $product->setDateLastModified($currentDatetime)
                        ->setName($manufacturerProduct->getName())
                        ->setDescription($manufacturerProduct->getDescription())
                        ->setIsCod($manufacturerProduct->getIsCod())
                        ->setCondition($manufacturerProduct->getCondition())
                        ->setShortDescription($manufacturerProduct->getShortDescription())
                        ->setProductCategory($manufacturerProduct->getProductCategory())
                        ->setBrand($manufacturerProduct->getBrand())
                        ->setKeywords($manufacturerProduct->getKeywords());

                if($productStatus){
                    $product->setStatus($productStatus);
                }

                // updating product unit
                $productUnits = array();
                foreach ($product->getUnits() as $unit) {
                    $productUnits[] = $unit->getProductUnitId();
                }

                $qmarks = "";
                $productAttributeNames = $product->getAttributes();
                $productAttributeNameIds = array();
                foreach($productAttributeNames as $productAttributeName){
                    $productAttributeNameIds[] = $productAttributeName->getProductAttributeNameId();
                    $qmarks .= "?,";
                }

                /**
                 * Delete all old attributes. These will be regenerated.
                 * Use more performant raw query.
                 */
                if(count($productAttributeNameIds) > 0){
                    $qmarks = rtrim($qmarks, ',');
                    $attributeValueSql = "
                            DELETE FROM ProductAttributeValue
                            WHERE product_attribute_name_id IN (".$qmarks.")
                        ";
                    $stmt = $this->entityManager->getConnection()->prepare($attributeValueSql);
                    $stmt->execute($productAttributeNameIds);
                }
                $attributeNameSql = "
                            DELETE FROM ProductAttributeName
                            WHERE product_id = :productId
                        ";
                $params = array('productId' => $product->getProductId());
                $stmt = $this->entityManager->getConnection()->prepare($attributeNameSql);
                $stmt->execute($params);

                $manufacturerProductUnits = array();
                $indexedManufacturerProductUnits = array();
                foreach ($manufacturerProduct->getRetailPriceSetUnits() as $unit) {
                    $manufacturerProductUnits[] = $unit->getManufacturerProductUnitId();
                    $indexedManufacturerProductUnits[$unit->getManufacturerProductUnitId()] = $unit;
                }

                $manufacturerProductUnitMaps = $manufacturerProductUnitMapRepo->findBy (array(
                    'manufacturerProductUnit' => $manufacturerProductUnits,
                    'productUnit' => $productUnits,
                ));

                $updatedManufacturerProductUnitIds = [];
                foreach ($manufacturerProductUnitMaps as $manufacturerProductUnitMap) {

                    if ($manufacturerProductUnitMap) {
                        $manufacturerProductUnit = $manufacturerProductUnitMap->getManufacturerProductUnit();
                        $productUnit = $manufacturerProductUnitMap->getProductUnit();
                        $updatedManufacturerProductUnitIds[] = $manufacturerProductUnit->getManufacturerProductUnitId();

                        $productUnit->setSku($manufacturerProductUnit->getSku())
                                    ->setDateLastModified($currentDatetime)
                                    ->setPrice($manufacturerProductUnit->getPrice())
                                    ->setDiscountedPrice($manufacturerProductUnit->getRetailPrice())
                                    ->setWidth($manufacturerProductUnit->getWidth())
                                    ->setHeight($manufacturerProductUnit->getHeight())
                                    ->setLength($manufacturerProductUnit->getLength())
                                    ->setWeight($manufacturerProductUnit->getWeight());

                        /**
                         * Re-create attributes
                         */
                        foreach ($manufacturerProductUnit->getManufacturerProductAttributeValues() as $attributeValue) {
                            $manufacturerProductAttributeName = $attributeValue->getManufacturerProductAttributeName();

                            $productAttributeName = $productAttributeNameRepo->findOneBy(array(
                                'name'    => $manufacturerProductAttributeName->getName(),
                                'product' => $product
                            ));
                            if($productAttributeName == null){
                                $productAttributeName = new ProductAttributeName();
                                $productAttributeName->setName($manufacturerProductAttributeName->getName());
                                $productAttributeName->setProduct($product);
                                $this->entityManager->persist($productAttributeName);
                            }

                            $productAttributeValue = $productAttributeValueRepo->findOneBy(array(
                                'productAttributeName' => $productAttributeName,
                                'productUnit'          => $productUnit
                            ));

                            if ($productAttributeValue == null) {
                                $productAttributeValue = new ProductAttributeValue();
                                $productAttributeValue->setProductUnit($productUnit);
                                $productAttributeValue->setProductAttributeName($productAttributeName);
                                $this->entityManager->persist($productAttributeValue);
                            }

                            $productAttributeValue->setValue($attributeValue->getValue());
                            $this->entityManager->flush();
                        }
                    }
                }

                /**
                 * Add non-existing manufacturer product units to the entity
                 */
                $nonExistingManufacturerUnitIds = array_diff($manufacturerProductUnits, $updatedManufacturerProductUnitIds);
                $newProductUnits = array();
                $newManufacturerProductAttributeUnitMap = array();
                foreach($nonExistingManufacturerUnitIds as $key => $nonExistingManufacturerUnitId){
                    $mpu = $indexedManufacturerProductUnits[$nonExistingManufacturerUnitId];
                    $productUnit = new ProductUnit();
                    $productUnit->setProduct($product);
                    $productUnit->setSku($mpu->getSku());
                    $productUnit->setDateCreated($currentDatetime);
                    $productUnit->setDateLastModified($currentDatetime);
                    $productUnit->setPrice($mpu->getPrice());
                    $productUnit->setDiscountedPrice($mpu->getRetailPrice());
                    $productUnit->setWidth($mpu->getWidth());
                    $productUnit->setHeight($mpu->getHeight());
                    $productUnit->setLength($mpu->getLength());
                    $productUnit->setWeight($mpu->getWeight());
                    $this->entityManager->persist($productUnit);
                    $manufacturerProductUnitMap = new ManufacturerProductUnitMap();
                    $manufacturerProductUnitMap->setProductUnit($productUnit);
                    $manufacturerProductUnitMap->setManufacturerProductUnit($mpu);
                    $this->entityManager->persist($manufacturerProductUnitMap);
                    /**
                     * Create mapping for product unit to manufacturer product attribute value
                     */
                    $newProductUnits[$key] = $productUnit;
                    foreach($mpu->getManufacturerProductAttributeValues() as $attributeValue){
                        $newManufacturerProductAttributeUnitMap[$attributeValue->getManufacturerProductAttributeValueId()] = $key;
                    }
                }
                if(count($newManufacturerProductAttributeUnitMap) > 0){
                    foreach($manufacturerProduct->getAvailableAttributes() as $attribute){
                        $productAttributeName = $productAttributeNameRepo->findOneBy(array(
                            'name'    => $attribute['groupName'],
                            'product' => $product
                        ));
                        if($productAttributeName == null){
                            $productAttributeName = new ProductAttributeName();
                            $productAttributeName->setName($attribute['groupName']);
                            $productAttributeName->setProduct($product);
                            $this->entityManager->persist($productAttributeName);
                        }

                        foreach($attribute['items'] as $attributeValue){
                            if(isset($newManufacturerProductAttributeUnitMap[$attributeValue['id']])){
                                $key = $newManufacturerProductAttributeUnitMap[$attributeValue['id']];
                                $productUnit = $newProductUnits[$key];
                                $productAttributeValue = new ProductAttributeValue();
                                $productAttributeValue->setProductUnit($productUnit);
                                $productAttributeValue->setProductAttributeName($productAttributeName);
                                $productAttributeValue->setValue($attributeValue['name']);
                                $this->entityManager->persist($productAttributeValue);
                            }
                        }
                        $this->entityManager->flush();
                    }
                }

                if($syncImages){
                    /**
                     * Synchronize images
                     */
                    if (!file_exists($productImageDirectory)) {
                        mkdir($productImageDirectory , 0777);
                    }

                    foreach ($manufacturerProduct->getImages() as $image) {
                        $productImage = $productImageRepo->findOneBy(array(
                            'imageLocation' => $image->getRawImageLocation(),
                            'product'       => $product
                        ));

                        if ($productImage) {
                            $productImage->setIsDeleted($image->getIsDelete())
                                         ->setIsPrimary($image->getIsPrimary());
                        }
                        else {
                            $productImage = new ProductImage();
                            $productImage->setProduct($product);
                            $productImage->setImageLocation($image->getRawImageLocation());
                            $productImage->setIsDeleted($image->getIsDelete());
                            $productImage->setIsPrimary($image->getIsPrimary());
                            $this->entityManager->persist($productImage);

                            $sourceImage = 'web/'.self::MANUFACTURER_PRODUCT_IMAGE_DIRECTORY.DIRECTORY_SEPARATOR.$image->getImageLocation();
                            $destinationImage = $productImageDirectory.DIRECTORY_SEPARATOR.$productImage->getRawImageLocation();

                            if($adapter instanceof AwsS3){
                                $content = file_get_contents(rtrim($this->assetHostname, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR. urlencode(ltrim($sourceImage, "web/")));
                                file_put_contents($destinationImage, $content);
                            }
                            else{
                                copy($sourceImage, $destinationImage);
                            }

                            $this->fileUploader->createImageWithDifferentSizes($destinationImage, $product->getProductId());

                            if($adapter instanceof AwsS3){
                                $file = new File($destinationImage);
                                $filepath = ltrim($file->getPathname(), "web/");
                                $adapter->setMetadata($filepath, array('contentType' => $file->getMimeType()));
                                $adapter->write($filepath, file_get_contents($file->getPathname()));
                            }
                            echo "\nUploaded ".$destinationImage;
                        }
                    }

                    if($adapter instanceof AwsS3){
                        $fs = new fs($productImageDirectory);
                        $fs->remove(array($productImageDirectory));
                    }
                }

                $this->entityManager->flush();
                return array('isSuccessful' => true);
            }
            catch(\Exception $e){
                return array('isSuccessful' => false, 'error' => $e->getMessage());
            }
        }

        return array('isSuccessful' => false);
    }

    private function addProductCountry($product, $manufacturerProduct)
    {
        $productCountry = new ProductCountry;

        $manufacturerProductCountry = $this->entityManager
            ->getRepository('YilinkerCoreBundle:ManufacturerProductCountry')
            ->findOneByManufacturerProduct($manufacturerProduct);

        $productCountry->setProduct($product)
                       ->setCountry($manufacturerProductCountry->getCountry())
                       ->setStatus(Product::ACTIVE);

        $this->entityManager->persist($productCountry);

        return true;
    }

    private function getProductLocale($manufacturerProduct)
    {
        $manufacturerProductCountry = $this->entityManager
            ->getRepository('YilinkerCoreBundle:ManufacturerProductCountry')
            ->findOneByManufacturerProduct($manufacturerProduct);

        if ($manufacturerProductCountry) {

            $country = $manufacturerProductCountry->getCountry();

            $languageCountry = $this->entityManager
                ->getRepository('YilinkerCoreBundle:LanguageCountry')
                ->findOneBy(array(
                    'country' => $country,
                    'isPrimary' => true,
                ));

            if ($languageCountry) {
                $language = $languageCountry->getLanguage();

                return $language->getCode();
            }
        }

        return $this->yilinkerTranslatable->getListenerLocale();
    }

    /**
     * Calculates the affiliate commision
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $manufacturerProductUnit
     * @return string
     */
    public function calculateCommision($manufacturerProductUnit)
    {
        $itemCost = $manufacturerProductUnit->getUnitPrice();
        $sellingPrice = $manufacturerProductUnit->getDiscountedPrice();

        $netPrice = bcsub($sellingPrice, $itemCost, 4);
        $inputTax = bcsub($itemCost,bcdiv($itemCost, bcadd("1.0000", bcdiv($this->taxPercentage, "100.00", 4),4)), 4);
        $outputTax = bcsub($sellingPrice, bcdiv($sellingPrice, bcadd("1.0000", bcdiv($this->taxPercentage, "100.00", 4),4)), 4);
        $netTax = bcsub($outputTax, $inputTax, 4);

        $commision = bcmul(bcsub($netPrice, $netTax, 4), bcdiv($this->comissionMultiplerPercentage, "100.00", 4), 4);

        return $commision;
    }

}
