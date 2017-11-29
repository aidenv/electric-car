<?php

namespace Yilinker\Bundle\CoreBundle\Command\Migrate;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\InhouseProduct;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\InhouseProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\InhouseProductUser;
use Yilinker\Bundle\CoreBundle\Entity\ProductCountry;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductAttributeName;
use Yilinker\Bundle\CoreBundle\Entity\ProductAttributeValue;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit;
use Symfony\Component\HttpFoundation\File\File;
use Gaufrette\Adapter\AwsS3;
use Symfony\Component\Filesystem\Filesystem as fs;
use Yilinker\Bundle\MerchantBundle\Services\Reseller\ResellerUploader;

class ManufacturerProductToInhouseProductCommand extends ContainerAwareCommand
{
    private $output;
    private $entityManager;
    private $yilinkerTranslatable;
    private $kernel;
    private $filesystem;
    private $fileUploader;
    private $country;

    protected function configure()
    {
        $this
            ->setName('yilinker-migrations:manufacturer-product-to-inhouse-product')
            ->setDescription('Migrate data from ManufacturerProduct to InhouseProduct table')
            ->addOption(
                'lastId',
                null,
                InputOption::VALUE_REQUIRED,
                'from what page',
                0
            )
            ->addOption(
                'pages',
                null,
                InputOption::VALUE_REQUIRED,
                'to what page',
                20
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        gc_enable();
        $this->output = $output;

        $container = $this->getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->yilinkerTranslatable = $container->get('yilinker_core.translatable.listener');
        $this->yilinkerTranslatable->setCountry('ph');
        $this->kernel = $container->get('kernel');
        $this->filesystem = $container->get('photo_storage_filesystem');
        $this->fileUploader = $container->get('yilinker_merchant.service.product_file_uploader');
        $this->fileUploader->setUploadDirectory('web/'.$this->fileUploader->getUploadDirectory());

        $tbManufacturerProduct = $this->entityManager->getRepository('YilinkerCoreBundle:ManufacturerProduct');
        $page = 1;
        $lastId = $input->getOption('lastId', 0);
        $toPage = $input->getOption('pages', 20);
        while (
            $page <= $toPage &&
            $manufacturerProducts = $tbManufacturerProduct
                ->qb()
                ->leftJoin('this.product', 'product')
                ->andWhere('this.manufacturerProductId > :lastId')
                ->andWhere('product IS NULL')
                ->setParameter('lastId', $lastId)
                ->setLimit(100)
                ->orderBy('this.manufacturerProductId', 'ASC')
                ->page($page++)
                ->getResult()
            ) {
            $this->process($manufacturerProducts);
            unset($manufacturerProducts);
            $this->entityManager->clear();
        }

        $this->output->writeln('Done!!!');
    }

    private function process($manufacturerProducts)
    {
        $manufacturerProduct = array_shift($manufacturerProducts);
        if ($manufacturerProduct) {
            $this->createInhouseProduct($manufacturerProduct);
            gc_collect_cycles();
            $this->output->writeln('Memory Usage: '.(memory_get_peak_usage(true)/1000000).'MB');
            $this->process($manufacturerProducts);
        }
    }
    
    private function createInhouseProduct($manufacturerProduct)
    {
        $manufacturerProduct->disabledTimestamp = true;
        $container = $this->getContainer();
        $tbUser = $this->entityManager->getRepository('YilinkerCoreBundle:User');
        $user = $tbUser->getInhouseUser();
        $this->country = $this->entityManager->getRepository('YilinkerCoreBundle:Country')->findOneBy(array('code' => 'ph'));

        $truncatedShortDescription = substr($manufacturerProduct->getShortDescription(), 0, (ManufacturerProduct::SHORT_DESCRIPTION_LENGTH - 1));
        $primaryImage = $manufacturerProduct->getPrimaryImage();

        $product = new InhouseProduct();
        $product->disabledTimestamp = true;
        $product->setManufacturerProduct($manufacturerProduct);
        $product->setReferenceNumber($manufacturerProduct->getReferenceNumber());
        $product->setManufacturer($manufacturerProduct->getManufacturer());
        $product->setUser($user);
        $product->setDateCreated($manufacturerProduct->getDateAdded());
        $product->setDateLastModified($manufacturerProduct->getDateLastModified());
        $product->setClickCount(0);
        $product->setName($manufacturerProduct->getName());
        $product->setDescription($manufacturerProduct->getDescription());
        $product->setShortDescription($truncatedShortDescription);
        $product->setIsCod($manufacturerProduct->getIsCod());
        $product->setCondition($manufacturerProduct->getCondition());
        $product->setProductCategory($manufacturerProduct->getProductCategory());
        $product->setBrand($manufacturerProduct->getBrand());
        $product->setStatus($manufacturerProduct->getEquivalentProductStatus());
        $product->setKeywords($manufacturerProduct->getKeywords());
        $product->setCountry($this->country);
        $product->setCountryCode($this->country->getCode(true));

        $productCountry = new ProductCountry;
        $productCountry->setProduct($product)
                       ->setCountry($this->country)
                       ->setStatus($manufacturerProduct->getEquivalentProductStatus());

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

        foreach($manufacturerProduct->getUnits() as $key => $manufacturerProductUnit){
            $manufacturerProductUnit->disabledTimestamp = true;
            $productUnit = new InhouseProductUnit();
            $productUnit->disabledTimestamp = true;
            $productUnit->setManufacturerProductUnit($manufacturerProductUnit);
            $productUnit->setReferenceId($manufacturerProductUnit->getReferenceId());
            $productUnit->setRetailPrice($manufacturerProductUnit->getRetailPrice());
            $productUnit->setUnitPrice($manufacturerProductUnit->getUnitPrice());
            $productUnit->setMoq($manufacturerProductUnit->getMoq());
            $productUnit->setShippingFee($manufacturerProductUnit->getShippingFee());
            $productUnit->setIsInventoryConfirmed($manufacturerProductUnit->getIsInventoryConfirmed());
            $productUnit->setLocale($this->country->getCode(true));

            $productUnit->setQuantity($manufacturerProductUnit->getQuantity());
            $productUnit->setProduct($product);
            $productUnit->setSku($manufacturerProductUnit->getSku());
            $productUnit->setDateCreated($manufacturerProductUnit->getDateCreated());
            $productUnit->setDateLastModified($manufacturerProductUnit->getDateLastModified());
            $productUnit->setPrice($manufacturerProductUnit->getPrice());
            $productUnit->setDiscountedPrice($manufacturerProductUnit->getDiscountedPrice());
            $productUnit->setWidth($manufacturerProductUnit->getWidth());
            $productUnit->setHeight($manufacturerProductUnit->getHeight());
            $productUnit->setLength($manufacturerProductUnit->getLength());
            $productUnit->setWeight($manufacturerProductUnit->getWeight());
            $productUnit->setCommission($manufacturerProductUnit->getCommission());

            $productUnit->setStatus(
                $manufacturerProductUnit->getStatus() == ManufacturerProductUnit::MANUFACTURER_PRODUCT_STATUS_ACTIVE?
                    ProductUnit::STATUS_ACTIVE : ProductUnit::STATUS_INACTIVE
            );

            $this->entityManager->persist($productUnit);

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

        $this->inhouseProductUser($manufacturerProduct, $product);
        $this->entityManager->persist($product);

        try {
            $this->entityManager->flush();
            try{
                $productImageDirectory = 'web/'.ResellerUploader::PRODUCT_IMAGE_DIRECTORY.DIRECTORY_SEPARATOR.$product->getProductId();
                if (!file_exists($productImageDirectory)) {
                    mkdir($productImageDirectory , 0777);
                }
                $adapter = $this->filesystem->getAdapter();
                foreach($imagesUploads as $imageUpload){

                    $destinationImage = $productImageDirectory.DIRECTORY_SEPARATOR.$imageUpload['destination'];
                    $sourceImage = ResellerUploader::MANUFACTURER_PRODUCT_IMAGE_DIRECTORY.DIRECTORY_SEPARATOR.$imageUpload['source'];
                    if($adapter instanceof AwsS3){
                        $content = file_get_contents(rtrim($container->getParameter('asset_hostname'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR. urlencode($sourceImage));
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

                $this->output->writeln('ManufacturerProduct #'.$manufacturerProduct->getManufacturerProductId().': successfully created InhouseProduct #'.$product->getProductId());
            }
            catch(\Exception $e){
                $this->output->writeln('ManufacturerProduct #'.$manufacturerProduct->getManufacturerProductId().': inserted in database as InhouseProduct #'.$product->getProductId().' but images were not made - '.$e->getMessage());
            }   
        } catch (\Exception $e) {
            $this->output->writeln('ManufacturerProduct #'.$manufacturerProduct->getManufacturerProductId().': failed to create InhouseProduct - '.$e->getMessage());
        }
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

    private function inhouseProductUser($manufacturerProduct, $product)
    {
        $maps = $manufacturerProduct->getManufacturerProductMaps();
        foreach ($maps as $map) {
            $originalProduct = $map->getProduct();
            $originalProduct->setCountry($this->country);

            $inhouseProductUser = new InhouseProductUser;
            $inhouseProductUser->setUser($originalProduct->getUser());
            $inhouseProductUser->setProduct($product);
            $inhouseProductUser->setStatus($originalProduct->getStatus());
            $inhouseProductUser->setDateAdded($map->getDateAdded());
            $this->entityManager->persist($inhouseProductUser);
        }
    }
}