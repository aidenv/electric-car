<?php

namespace Yilinker\Bundle\MerchantBundle\Services\Product;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\Country;
use Yilinker\Bundle\CoreBundle\Entity\Brand;
use Yilinker\Bundle\CoreBundle\Entity\CustomBrand;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\ProductCountry;
use Yilinker\Bundle\CoreBundle\Entity\ProductAttributeName;
use Yilinker\Bundle\CoreBundle\Entity\ProductAttributeValue;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductPromoMap;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnitImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductGroup;
use Yilinker\Bundle\CoreBundle\Entity\UserProductGroup;
use Yilinker\Bundle\MerchantBundle\Services\FileUpload\FileUploader;
use Yilinker\Bundle\MerchantBundle\Services\FileUpload\ProductFileUploader;
use Yilinker\Bundle\CoreBundle\Helpers\StringHelper;

class ProductUploader
{
    const SKU_LETTER_LEN = 5;

    const SKU_NUMBER_LEN = 5;

    /**
     * @var \Doctrine\ORM\EntityManager|Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Yilinker\Bundle\Entity\Service\EntityService
     */
    private $entityService;

    private $container;

    private $requestStack;

    private $translationService;

    /**
     * @param EntityManager $entityManager
     * @param $entityService
     */
    public function __construct(
        EntityManager $entityManager,
        $entityService,
        $requestStack,
        $translationService
    ){
        $this->em = $entityManager;
        $this->entityService = $entityService;
        $this->requestStack = $requestStack;
        $this->translationService = $translationService;
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Add Product
     * @param Product $product
     * @return Product
     */
    public function addProduct (Product $product)
    {
        $product->setDateCreated(Carbon::now());
        $product->setDateLastModified(Carbon::now());
        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    /**
     * Add ProductImage
     * @param Product $product
     * @param string $imageLocation
     * @param bool $isPrimary
     * @param $locale
     * @return ProductImage
     */
    public function addProductImage (Product $product, $imageLocation, $isPrimary = false, $locale = null)
    {
        $productImage = new ProductImage();
        $productImage->setImageLocation($imageLocation);
        $productImage->setProduct($product);
        $productImage->setIsPrimary($isPrimary);

        if (!is_null($locale)) {
            $productImage->setDefaultLocale($locale);
        }

        $this->em->persist($productImage);
        $this->em->flush();

        return $productImage;
    }

    /**
     * Add ProductUnit Image
     * @param ProductUnit $productUnit
     * @param $fileName
     * @return ProductUnitImage
     */
    public function addProductUnitImage (ProductUnit $productUnit, $fileName)
    {
        $productUnitImage = new ProductUnitImage();
        $productUnitImage->setProductUnit($productUnit);
        $productUnitImage->setProductImage($fileName);
        $this->em->persist($productUnitImage);
        $this->em->flush();

        return $productUnitImage;
    }

    /**
     * Add ProductAttributeName
     * @param Product $product
     * @param string $attributeName
     * @return ProductAttributeName
     */
    public function addProductAttributeName (Product $product, $attributeName)
    {
        $productAttributeName = new ProductAttributeName();
        $productAttributeName->setProduct($product);
        $productAttributeName->setName($attributeName);
        $this->em->persist($productAttributeName);
        $this->em->flush();

        return $productAttributeName;
    }

    /**
     * Add ProductAttributeValue
     * @param ProductAttributeName $productAttributeName
     * @param ProductUnit $productUnit
     * @param string $value
     * @return ProductAttributeValue
     */
    public function addProductAttributeValue (ProductAttributeName $productAttributeName,
                                              ProductUnit $productUnit,
                                              $value)
    {
        $productAttributeValue = new ProductAttributeValue();
        $productAttributeValue->setProductAttributeName($productAttributeName);
        $productAttributeValue->setProductUnit($productUnit);
        $productAttributeValue->setValue($value);
        $this->em->persist($productAttributeValue);
        $this->em->flush();

        return $productAttributeValue;
    }

    /**
     * Add Product Unit
     * @param Product $product
     * @param $quantity
     * @param $sku
     * @param $price
     * @param $discountedPrice
     * @param $weight
     * @param $length
     * @param $width
     * @param $height
     * @param $status
     * @return ProductUnit
     */
    public function addProductUnit (Product $product, $quantity, $sku, $price, $discountedPrice, $weight, $length, $width, $height, $status, $locale = null)
    {
        $productUnit = new ProductUnit();
        $productUnit->setDateCreated(Carbon::now());
        $productUnit->setDateLastModified(Carbon::now());
        $productUnit->setProduct($product);
        $productUnit->setSku($sku);
        $productUnit->setPrice($price);
        $productUnit->setDiscountedPrice($discountedPrice);
        $productUnit->setWeight($weight);
        $productUnit->setLength($length);
        $productUnit->setWidth($width);
        $productUnit->setHeight($height);
        $productUnit->setStatus($status);
        if (!is_null($locale)) {
            $productUnit->setLocale($locale);
        }
        $this->em->persist($productUnit);
        $this->em->flush();
        $this->generateInternalSku($productUnit);

        return $productUnit;
    }

    /**
     * Remove Custom Brand By Product
     *
     * @param Product $product
     */
    public function removeCustomBrandByProduct (Product $product)
    {
        $customBrands = $this->em->getRepository('YilinkerCoreBundle:CustomBrand')->findByProduct($product);

        if (sizeof($customBrands) > 0) {

            foreach ($customBrands as $customBrand) {
                $this->em->remove($customBrand);
            }

            $this->em->flush();
        }
    }

    public function updateProductGroups(Product $product, $groups)
    {
        $userProductGroupRepository = $this->em->getRepository('YilinkerCoreBundle:UserProductGroup');
        $productGroupRepository = $this->em->getRepository('YilinkerCoreBundle:ProductGroup');

        $user = $product->getUser();
        $productGroups = $product->getProductGroups();

        if (is_array($groups)) {
            $groups = array_filter($groups);

            if (count($groups) > 0) {
                $arrayGroup = array();
                if ($productGroups) {
                  foreach ($productGroups as $group) {
                      $name = trim($group->getUserProductGroup()->getName());
                      if (!in_array($name, $groups)) {
                          $this->em->remove($group);
                      }
                      else {
                          $arrayGroup[] = $name;
                      }
                  }
                }

                $newGroups = array_diff($groups, $arrayGroup);

                foreach ($newGroups as $group) {
                    $group = trim($group);

                    $userProductGroup = $userProductGroupRepository->findOneBy(array(
                        'name' => $group,
                        'user' => $user
                    ));

                    if (!$userProductGroup) {
                        $userProductGroup = new UserProductGroup;
                        $userProductGroup->setUser($user)
                                         ->setName($group);

                        $this->em->persist($userProductGroup);
                    }

                    $productGroup = $productGroupRepository->findOneBy(array(
                        'product' => $product,
                        'userProductGroup' => $userProductGroup
                    ));

                    if (!$productGroup) {
                        $newProductGroup = new ProductGroup;
                        $newProductGroup->setUserProductGroup($userProductGroup)
                                        ->setProduct($product);

                        $this->em->persist($newProductGroup);
                    }
                }
            }
            else {
                if ($productGroups) {
                    foreach ($productGroups as $group) {
                        $this->em->remove($group);
                    }
                }
            }
            $this->em->flush();
        }

        return true;
    }

    /**
     * Add new Custom brand
     *
     * @param Product $product
     * @param $name
     * @return CustomBrand
     */
    public function addCustomBrand (Product $product, $name)
    {
        $this->removeCustomBrandByProduct ($product);
        $customBrand = new CustomBrand();
        $customBrand->setProduct($product);
        $customBrand->setName($name);
        $this->em->persist($customBrand);
        $this->em->flush();

        return $customBrand;
    }

    /**
     * Get Product Upload Details
     * @param Product $product
     * @return array
     */
    public function getProductUploadDetails (Product $product)
    {
        $productUnitRepository = $this->em->getRepository("YilinkerCoreBundle:ProductUnit");
        $productUnitImageRepository = $this->em->getRepository("YilinkerCoreBundle:ProductUnitImage");
        $productAttributeNameRepository = $this->em->getRepository('YilinkerCoreBundle:ProductAttributeName');
        $productAttributeValueRepository = $this->em->getRepository('YilinkerCoreBundle:ProductAttributeValue');
        $productImageRepository = $this->em->getRepository("YilinkerCoreBundle:ProductImage");
        $productUnitEntities = $productUnitRepository->findByProduct($product);
        $productUnitContainer = array();

        $shippingCategory = "";
        if ($product->getShippingCategory()) {
            $product->getShippingCategory()->setLocale($product->getLocale());
            $this->em->refresh($product->getShippingCategory());
            $shippingCategory = $product->getShippingCategory()->getName();
        }

        $productUploadDetails['productEntity'] = $product;
        $productUploadDetails['shippingCategory'] = $shippingCategory;
        $productUploadDetails['brandEntity'] = $product->getBrand();
        $productUploadDetails['productAndCategoryAttributes'] = $product->getProductCategory() !== null ? $this->getProductAndCategoryAttribute($product) : null;
        $productUploadDetails['productImageEntity'] = array();

        if ( $product->getBrand() !== null && (int) $product->getBrand()->getBrandId() === Brand::CUSTOM_BRAND_ID && $product->getCustomBrand()) {
            $productUploadDetails['brandEntity'] = $product->getCustomBrand()->first();
        }
        $images = $productImageRepository->findBy(array(
                                               'product'       => $product->getProductId(),
                                               'defaultLocale' => $product->getLocale(),
                                               'isDeleted'     => false
                                           ));

        foreach ($images as $productImage) {
            $productUploadDetails['productImageEntity'][] = array(
                'id'            => $productImage->getProductImageId(),
                'path'          => $productImage->getImageLocation(),
                'image'         => $productImage->getRawImageLocation(),
                'isPrimary'     => $productImage->getIsPrimary(),
                'defaultLocale' => $productImage->getDefaultLocale(),
            );
        }

        foreach ($productUnitEntities as $productUnitEntity) {
            $productUnitImages = $productUnitImageRepository->findByProductUnit($productUnitEntity->getProductUnitId());
            $productAttributeNames = $productAttributeNameRepository
                                     ->findByProduct($product->getProductId());
            $productAttributeArray = array();
            $productUnitImageArray = array();
            $productUnitId = '';

            //Get ProductAttributes
            if ($productAttributeNames) {

                foreach ($productAttributeNames as $productAttributeName) {
                    $attributeValues = $productAttributeValueRepository
                                      ->findBy(array(
                                          'productAttributeName' => $productAttributeName->getProductAttributeNameId(),
                                          'productUnit' => $productUnitEntity->getProductUnitId()
                                      ));
                    foreach($attributeValues as  $attributeValue) {
                        $productAttributeArray[] = array(
                            'attrNameId' => $productAttributeName->getProductAttributeNameId(),
                            'name' => $productAttributeName->getName(),
                            'attrValueId' => $attributeValue->getProductAttributeValueId(),
                            'value' => $attributeValue->getValue()
                        );

                        $productUnitId .= strtoupper($attributeValue->getValue());
                    }
                }

            }

            //Get ProductUnitImages
            if ($productUnitImages) {

                foreach ($productUnitImages as $productUnitImage) {
                    $productImage = $productImageRepository->find($productUnitImage->getProductImage());
                    $productUnitImageArray[] = array(
                        'id' => $productImage->getProductImageId(),
                        'name' => $productImage->getRawImageLocation(),
                        'isNew' => false
                    );
                }

            }

            $discount = 0;

            if (floatval($productUnitEntity->getPrice()) !== 0.00 && $productUnitEntity->getPrice() &&
                floatval($productUnitEntity->getDiscountedPrice()) !== 0.00 && $productUnitEntity->getDiscountedPrice()
                ) {
                $discount = 100 - (100 / ($productUnitEntity->getPrice() / $productUnitEntity->getDiscountedPrice()) );
            }

            $productUnitContainer[] = array(
                'id'              => $productUnitId,
                'productUnitId'   => $productUnitEntity->getProductUnitId(),
                'quantity'        => $productUnitEntity->getQuantity(),
                'sku'             => $productUnitEntity->getSku(),
                'price'           => $productUnitEntity->getPrice(),
                'discountedPrice' => $productUnitEntity->getDiscountedPrice(),
                'discount'        => $discount,
                'unitLength'      => $productUnitEntity->getLength(),
                'unitWidth'       => $productUnitEntity->getWidth(),
                'unitHeight'      => $productUnitEntity->getHeight(),
                'unitWeight'      => $productUnitEntity->getWeight(),
                'attributes'      => $productAttributeArray,
                'images'          => $productUnitImageArray
            );

        }
        $productUploadDetails['hasCombination'] = isset($productAttributeArray) ? sizeof($productAttributeArray) > 0 : false;
        $productUploadDetails['productUnit'] = $productUnitContainer;

        return $productUploadDetails;
    }

    /**
     * Get Product Attribute combined with category attribute
     * @param Product $product
     * @return array
     */
    public function getProductAndCategoryAttribute (Product $product)
    {
        $productCategoryEntities = $this->em->getRepository('YilinkerCoreBundle:ProductCategory')->find($product->getProductCategory()->getProductCategoryId());
        $categoryAttributes = $this->em->getRepository('YilinkerCoreBundle:CategoryAttributeName')
                                       ->getCategoryAttributeNameWithValue($productCategoryEntities);

        $productAttributeNameRepository = $this->em->getRepository('YilinkerCoreBundle:ProductAttributeName');
        $productAttributeValueRepository = $this->em->getRepository('YilinkerCoreBundle:ProductAttributeValue');
        $productAttributeNameEntities = $productAttributeNameRepository->findByProduct($product->getProductId());

        if ($productAttributeNameEntities) {

            //Get ProductAttribute
            foreach ($productAttributeNameEntities as $productAttributeNameEntity) {
                $productAttributeNameEntity->setLocale($product->getLocale());
                $this->em->refresh($productAttributeNameEntity);
                $productAttributeValueEntities = $productAttributeValueRepository
                                                 ->findByProductAttributeName($productAttributeNameEntity->getProductAttributeNameId());
                $productAttributeValue = array();
                $isAttributeExists = false;

                foreach ($productAttributeValueEntities as $productAttributeValueEntity) {
                    $productAttributeValueEntity->setLocale($product->getLocale());
                    $this->em->refresh($productAttributeValueEntity);

                    if (!in_array($productAttributeValueEntity->getValue(), $productAttributeValue)) {
                        $productAttributeValue[$productAttributeValueEntity->getProductAttributeValueId()] = $productAttributeValueEntity->getValue();
                    }
                    else {
                        $key = array_search($productAttributeValueEntity->getValue(), $productAttributeValue);
                        $productAttributeValue[$key . '-' .$productAttributeValueEntity->getProductAttributeValueId()] = $productAttributeValue[$key];
                        unset($productAttributeValue[$key]);
                    }

                }

                foreach ($categoryAttributes as $key => $categoryAttribute) {

                    if ($categoryAttribute['name'] === $productAttributeNameEntity->getName()) {
                        $isAttributeExists = true;
                        $categoryAttributes[$key]['values'] = array_unique(array_merge($productAttributeValue, $categoryAttribute['values']));
                    }

                }

                if (!$isAttributeExists && $productAttributeNameEntity->getName()) {

                    $categoryAttributes[] = array(
                        'attrNameId' => $productAttributeNameEntity->getProductAttributeNameId(),
                        'name' => $productAttributeNameEntity->getName(),
                        'values' => $productAttributeValue
                    );

                }

            }

        }

        return $categoryAttributes;
    }

    /**
     * Update Product
     * @param Product $product
     * @return Product
     */
    public function updateProduct (Product $product)
    {
        /**
         * Resold products have to re-reviewed on edit
         */
        if($product->getIsResold()){
            $product->setStatus(Product::FOR_REVIEW);
        }

        $product->setDateLastModified(Carbon::now());
        $this->em->flush();

        return $product;
    }

    /**
     * Remove Product Combination
     * @param Product $product
     */
    public function removeCombination(Product $product)
    {
        $productUnitRepository = $this->em->getRepository('YilinkerCoreBundle:ProductUnit');
        $productUnitEntities = $productUnitRepository->findByProduct($product);
        $productAttributeValueRepository = $this->em->getRepository('YilinkerCoreBundle:ProductAttributeValue');
        $productAttributeNameRepository = $this->em->getRepository('YilinkerCoreBundle:ProductAttributeName');
        $productUnitImageRepository = $this->em->getRepository("YilinkerCoreBundle:ProductUnitImage");

        foreach ($productUnitEntities as $productUnitEntity) {
            $productAttributeNameEntities = $productAttributeNameRepository->findByProduct($product->getProductId());
            $productUnitImageEntities = $productUnitImageRepository->findByProductUnit($productUnitEntity->getProductUnitId());

            foreach ($productAttributeNameEntities as $productAttributeNameEntity) {

                $productAttributeValueEntities = $productAttributeValueRepository->findBy(array(
                    'productAttributeName' => $productAttributeNameEntity->getProductAttributeNameId(),
                    'productUnit' => $productUnitEntity->getProductUnitId()
                ));

                foreach($productAttributeValueEntities as $productAttributeValueEntity) {
                    $this->em->remove($productAttributeValueEntity);
                }

                $this->em->remove($productAttributeNameEntity);

            }

            foreach ($productUnitImageEntities as $productUnitImageEntity) {
                $this->em->remove($productUnitImageEntity);
            }

            $this->em->remove($productUnitEntity);

        }

        $this->em->flush();

    }

    /**
     * @param Array $productUnitEntities
     * @return array
     */
    public function removeProductUnits (array $productUnitEntities)
    {
        $productUnitImageRepository = $this->em->getRepository("YilinkerCoreBundle:ProductUnitImage");
        $productPromoMapRepository = $this->em->getRepository("YilinkerCoreBundle:ProductPromoMap");
        $cartItemRepository = $this->em->getRepository("YilinkerCoreBundle:CartItem");
        $promoMapArray = array();

        foreach ($productUnitEntities as $productUnitEntity) {

            /**
             * Set ProductPromoMap.productUnit association to null
             */
            $productPromoMapEntity = $productPromoMapRepository->findOneByProductUnit($productUnitEntity);
            if ($productPromoMapEntity) {
                $promoMapArray[] = array(
                    'promoMapId' => $productPromoMapEntity->getProductPromoMapId(),
                    'attributeNameValuePair' => $this->getProductUnitNameValuePair ($productUnitEntity)
                );
                $productUnitEntity->removeProductPromoMap($productPromoMapEntity);
                $productPromoMapEntity->setProductUnit(null);
            }

            /**
             * Delete cartItems associated with the ProductUnit
             */
            $cartItems = $cartItemRepository->findBy(array(
                'productUnit' => $productUnitEntity,
            ));
            foreach($cartItems as $cartItem){
                $this->em->remove($cartItem);
            }

            $productAttributeValues = $productUnitEntity->getProductAttributeValues();
            $productUnitImageEntities = $productUnitImageRepository->findByProductUnit($productUnitEntity->getProductUnitId());

            foreach($productAttributeValues as $productAttributeValue) {
                $productAttributeName = $productAttributeValue->getProductAttributeName();
                $productAttributeValue->setProductAttributeName(null);
                $productAttributeValue->setProductUnit(null);
                $productUnitEntity->removeProductAttributeValue($productAttributeValue);
                $this->em->remove($productAttributeName);
                $this->em->remove($productAttributeValue);
            }

            foreach ($productUnitImageEntities as $productUnitImageEntity) {
                $this->em->remove($productUnitImageEntity);
            }

            // remove ProductUnitWarehouse
            $productUnitWarehouses = $this->em->getRepository('YilinkerCoreBundle:ProductUnitWarehouse')->findByProductUnit($productUnitEntity);
            foreach ($productUnitWarehouses as $productUnitWarehouse) {
                $this->em->remove($productUnitWarehouse);
            }

            $this->em->remove($productUnitEntity);

        }

        $this->em->flush();

        return $promoMapArray;
    }

    /**
     * Get ProductUnit Attribute Name and Attribute Value Pair. and concat into string
     *
     * @param ProductUnit $productUnitEntity
     * @return string
     */
    public function getProductUnitNameValuePair (ProductUnit $productUnitEntity)
    {
        $productAttributeValueRepository = $this->em->getRepository('YilinkerCoreBundle:ProductAttributeValue');
        $productAttributeNameRepository = $this->em->getRepository('YilinkerCoreBundle:ProductAttributeName');
        $product = $productUnitEntity->getProduct();

        $productAttributeNames = $productAttributeNameRepository->findByProduct($product->getProductId());
        $productAttributeString = '';

        //Get ProductAttributes
        if ($productAttributeNames) {

            foreach ($productAttributeNames as $productAttributeName) {
                $attributeValues = $productAttributeValueRepository
                                   ->findBy(array(
                                       'productAttributeName' => $productAttributeName->getProductAttributeNameId(),
                                       'productUnit' => $productUnitEntity->getProductUnitId()
                                   ));
                foreach($attributeValues as  $attributeValue) {
                    $productAttributeString .= strtoupper($productAttributeName->getName().$attributeValue->getValue());
                }
            }

        }

        return $productAttributeString;
    }

    /**
     * Update ProductPromoMap productUnit
     *
     * @param ProductPromoMap $productPromoMap
     * @param ProductUnit $productUnit
     */
    public function updateProductPromoMap (ProductPromoMap $productPromoMap, ProductUnit $productUnit)
    {
        $productPromoMap->setProductUnit($productUnit);
        $this->em->flush();
    }

    /**
     * Remove Product Image
     *
     * @param ProductImage $productImage
     */
    public function removeProductImage (ProductImage $productImage)
    {
        $productImage->setIsDeleted(true);
        $this->em->flush();
    }

    /**
     * Get Updated Product Status
     *
     * @param Product $product
     * @param array $changes
     * @return bool
     */
    public function getProductStatus (Product $product = null, $changes = array())
    {
        $status = Product::FOR_COMPLETION;

        if ($product !== null) {
            $status = (int) $product->getStatus();

            if ($status === Product::REJECT || $status === Product::DRAFT || $status === Product::DELETE) {
                $status = Product::FOR_REVIEW;
            }
            else if ($status === Product::INACTIVE || $status === Product::ACTIVE) {

                if (sizeof($changes) > 0) {
                    $productUnitChanges = $changes['productUnitChanges'];
                    $hasNewProductUnit = $changes['hasNewProductUnit'];
                    $productChanges = $changes['productChanges'];
                    $isQuantityChanged = false;

                    foreach ($productUnitChanges as $key => &$column) {

                        if (isset($column['quantity'])) {
                            unset($productUnitChanges[$key]);
                            $isQuantityChanged = true;
                        }

                    }

                    /**
                     * If merchant only edits product ( expect quantity )
                     */
                    if ($hasNewProductUnit === true ||
                        sizeof($productChanges) > 0 ||
                        (sizeof($productUnitChanges) > 0 && $isQuantityChanged === false) ) {
                        $status = Product::FOR_REVIEW;
                    }
                    /**
                     * If quantity only
                     */
                    else if (sizeof($productChanges) === 0 &&
                        (sizeof($productUnitChanges) === 0 && $isQuantityChanged === true) ) {
                        $status = Product::ACTIVE;
                    }

                }


            }

        }

        return $status;
    }

    /**
     * Get Changes in Product
     *
     * @param Product $product
     * @return mixed
     */
    public function getProductChanges (Product $product)
    {
        $arrayOfColumns = array (
            'brand',
            'productCategory',
            'name',
            'description',
            'shortDescription',
            'condition',
            'youtubeVideoUrl'
        );
        $productChanges = $this->entityService->getChanges($product, $arrayOfColumns, true);

        return $productChanges;
    }

    /**
     * Get Changes in Product Unit
     *
     * @param ProductUnit $productUnit
     * @return mixed
     */
    public function getProductUnitChanges (ProductUnit $productUnit)
    {
        $arrayOfColumns = array (
            'quantity',
            'sku',
            'price',
            'discountedPrice',
            'weight',
            'length',
            'width',
            'height'
        );
        $productUnitChanges = $this->entityService->getChanges($productUnit, $arrayOfColumns, true);

        return $productUnitChanges;
    }

    /**
     * Generate random Internal SKU
     *
     * @param ProductUnit $productUnit
     * @param $letterLength
     * @param $numberLength
     * @return mixed
     */
    public function generateInternalSku (ProductUnit $productUnit, $letterLength = self::SKU_LETTER_LEN, $numberLength = self::SKU_NUMBER_LEN)
    {
        $internalSku = "";
        $unique = false;

        while (!$unique) {
            $letters = StringHelper::generateRandomString($letterLength, true, false);
            $numbers = StringHelper::generateRandomString($numberLength, false, true);

            $internalSku = $letters . $numbers;
            $findProductUnit = $this->em->getRepository('YilinkerCoreBundle:ProductUnit')
                                        ->findOneByInternalSku($internalSku);
            if (!$findProductUnit) {
                $unique = true;
            }
        }

        $productUnit->setInternalSku($internalSku);
        $this->em->flush();

        return $productUnit->getInternalSku();
    }

    /**
     * Translate Product Attribute
     *
     * @param $locale
     * @param array $attributeNames
     * @param array $attributeValues
     * @return bool
     */
    public function translateProductAttribute ($locale, $attributeNames = array(), $attributeValues = array())
    {
        $attributeNameRepository = $this->em->getRepository('YilinkerCoreBundle:ProductAttributeName');
        $attributeValueRepository = $this->em->getRepository('YilinkerCoreBundle:ProductAttributeValue');
        $isTranslated = false;

        foreach ($attributeNames as $attributeName) {
            $attributeNameEntity = $attributeNameRepository->find($attributeName['id']);

            if ($attributeNameEntity instanceof ProductAttributeName && $attributeName['value'] !== '') {
                $attributeNameEntity->setLocale($locale);
                $newAttributeName = $attributeNameEntity->getName() == $attributeName['value'] ? $attributeName['value'] . '.' : $attributeName['value'];
                $attributeNameEntity->setName($newAttributeName);
                $isTranslated = true;
            }

        }

        foreach ($attributeValues as $attributeValue) {
            $attributeValueEntity = $attributeValueRepository->find($attributeValue['id']);

            if ($attributeValueEntity instanceof ProductAttributeValue && $attributeValue['value'] !== '') {
                $attributeValueEntity->setLocale($locale);
                $newAttributeValue = $attributeValueEntity->getValue() == $attributeValue['value'] ? $attributeValue['value'] . '.' : $attributeValue['value'];
                $attributeValueEntity->setValue($newAttributeValue);
                $isTranslated = true;
            }

        }

        $this->em->flush();

        return $isTranslated;
    }

    public function translateProductGroup(Product $product, array $productGroups = array())
    {
        $userProductGroupRepository = $this->em->getRepository('YilinkerCoreBundle:UserProductGroup');
        $locale = $product->getLocale();

        foreach ($productGroups as $group) {
            $userProductGroup = $userProductGroupRepository->find($group['id']);

            if ($userProductGroup && strlen($group['value'])) {
                $userProductGroup->setLocale($locale);
                $userProductGroup->setName($group['value']);
            }
        }

        return true;
    }

    /**
     * Translate product
     *
     * @param Product $product
     * @param $locale
     * @param $name
     * @param $fullDescription
     * @param $shortDescription
     * @param array $attributeNames
     * @param array $attributeValues
     * @return bool
     */
    public function translateProduct (
        Product $product,
        $locale,
        $name,
        $fullDescription,
        $shortDescription,
        $attributeNames = array(),
        $attributeValues = array(),
        $productGroups = array()
    ) {
        $product->setLocale($locale);

        if (!is_null($name) || $name !== '') {
            $product->setName($name);
        }

        if (!is_null($fullDescription) || $fullDescription !== '') {
            $product->setDescription($fullDescription);
        }

        if (!is_null($shortDescription) || $shortDescription !== '') {
            $product->setShortDescription($shortDescription);
        }

        $this->translateProductAttribute($locale, $attributeNames, $attributeValues);

        $this->em->flush();

        return true;
    }

    public function updateProductCountryStatus(Product $product, $status, $locale = null)
    {
        $productCountryRepository = $this->em->getRepository('YilinkerCoreBundle:ProductCountry');
        $languageRepository = $this->em->getRepository('YilinkerCoreBundle:Language');
        $languageCountryRepository = $this->em->getRepository('YilinkerCoreBundle:LanguageCountry');

        if (is_null($locale)) {
            $locale = $product->getDefaultLocale();
        }

        if ((int) $status === Product::FOR_COMPLETION) {
            $status = Product::FOR_REVIEW;
        }

        $acceptableStatus = array(
            Product::FOR_REVIEW,
            Product::ACTIVE,
            Product::DELETE,
            Product::REJECT,
        );

        if (!in_array($status, $acceptableStatus)) {
            return false;
        }

        $language = $languageRepository->findOneByCode($locale);
        if (!$language) {
            return false;
        }

        $languageCountries = $languageCountryRepository->findByLanguage($language);

        $countries = array();

        foreach ($languageCountries as $value) {
            $countries = $value->getCountry();
        }

        $productCountries = $productCountryRepository->findBy(array(
            'product' => $product,
            'country' => $countries
        ));

        foreach ($productCountries as $value) {
            $value->setStatus($status);
        }

        $this->em->flush();

        return true;
    }

    public function constructUploadDetails(
        $product,
        $locale = null,
        $forTranslation = false,
        $country = "ph"
    ){
        $productImageRepository = $this->em->getRepository("YilinkerCoreBundle:ProductImage");
        $assetsHelper = $this->container->get("templating.helper.assets");

        $defaultLocale = $product->getDefaultLocale();
        $shippingCategory = $product->getShippingCategory();

        if($shippingCategory){
            $shippingCategory->setLocale($locale);
            $this->em->refresh($shippingCategory);
        }

        $details = array(
            "productId" => $product->getProductId(),
            "name" => $product->getName(),
            "shortDescription" => $product->getShortDescription(),
            "description" => $product->getDescription(),
            "youtubeVideoUrl" => $product->getYoutubeVideoUrl(),
            "productConditionId" => $product->getCondition()? $product->getCondition()->getProductConditionId() : null,
            "productConditionName" => $product->getCondition()? $product->getCondition()->getName() : null,
            "productCategoryId" => $product->getProductCategory()? $product->getProductCategory()->getProductCategoryId() : null,
            "productCategoryName" => $product->getProductCategory()? $product->getProductCategory()->getName() : null,
            "shippingCategoryId" => $shippingCategory? $shippingCategory->getShippingCategoryId() : null,
            "shippingCategoryName" => $shippingCategory? $shippingCategory->getName() : null,
            "brandId" => $product->getBrand()? $product->getBrand()->getBrandId() : null,
            "status" => $product->getStatus(),
            "hasCombination" => false
        );

        if($product->getBrand() && $product->getBrand()->getBrandId() == Brand::CUSTOM_BRAND_ID){
            $customBrand = $product->getCustomBrand()->first();
            if($customBrand){
                $details["brandName"] = $customBrand->getName();
            }
            else{
                $details["brandName"] = "None";
            }
        }
        elseif($product->getBrand() && $product->getBrand()->getBrandId() != Brand::CUSTOM_BRAND_ID){
            $details["brandName"] = $product->getBrand()->getName();
        }
        else{
            $details["brandName"] = "None";
        }

        $productGroups = array();
        $groups = $product->getProductGroups();
        foreach($groups as $group){
            array_push($productGroups, $group->getUserProductGroup()->getName());
        }

        $details["productGroups"] = $productGroups;

        if($locale && !$forTranslation){
            $images = $product->getActiveImagesByLocale($locale);
        }
        else{
            $images = $product->getImages(false, true, $locale);
        }

        $productImages = array();
        foreach($images as $image){
            $imageDetails = $image->toArray(false);

            $image = array();
            $image["raw"] = $imageDetails["raw"];
            $image["imageLocation"] = $assetsHelper->getUrl($imageDetails["imageLocation"], "product");
            $image["sizes"]["thumbnail"] = $assetsHelper->getUrl($imageDetails["sizes"]["thumbnail"], "product");
            $image["sizes"]["small"] = $assetsHelper->getUrl($imageDetails["sizes"]["small"], "product");
            $image["sizes"]["medium"] = $assetsHelper->getUrl($imageDetails["sizes"]["medium"], "product");
            $image["sizes"]["large"] = $assetsHelper->getUrl($imageDetails["sizes"]["large"], "product");

            if($forTranslation){
                $image["isSelected"] = false;
                if($imageDetails["defaultLocale"] == $locale){
                    if(array_key_exists($imageDetails["raw"], $productImages)){
                        $productImages[$imageDetails["raw"]]["isSelected"] = true;
                    }
                    else{
                        $image["isSelected"] = true;
                    }
                }

            }

            $image["isPrimary"] = $imageDetails["isPrimary"];
            if($imageDetails["isPrimary"] && $imageDetails["defaultLocale"] != $locale){
                $image["isPrimary"] = false;
            }

            if(!array_key_exists($imageDetails["raw"], $productImages)){
                $productImages[$imageDetails["raw"]] = $image;
            }

        }

        $details["productImages"] = array_values($productImages);

        $productUnits = array();
        foreach($product->getUnits() as $unit){
            $unit->setLocale($country);
            $this->em->refresh($unit);

            $unitDetails = array(
                "productUnitId" => $unit->getProductUnitId(),
                "quantity" => $unit->getQuantity(),
                "sku" => $unit->getSku(),
                "price" => $unit->getPrice(),
                "discountedPrice" => $unit->getDiscountedPrice(),
                "discount" => $unit->getDiscount(),
                "length" => $unit->getLength(),
                "width" => $unit->getWidth(),
                "height" => $unit->getHeight(),
                "weight" => $unit->getWeight()
            );

            $attributes = array();
            foreach($unit->getProductAttributeValues() as $attributeValue){

                $attributeValue->setLocale($locale);
                $this->em->refresh($attributeValue);

                $attributeName = $attributeValue->getProductAttributeName();
                $attributeName->setLocale($locale);
                $this->em->refresh($attributeName);

                array_push($attributes, array(
                    "id" => $attributeName->getProductAttributeNameId(),
                    "name" => $attributeName->getName(),
                    "value" => $attributeValue->getValue()
                ));
            }

            $images = array();
            $unitImages = $productImageRepository->filterImages(
                            array(),
                            null,
                            $locale,
                            false,
                            false,
                            $unit,
                            false
                        );

            if(!empty($unitImages)){
                foreach($unitImages as $unitImage){
                    $imageDetails = $unitImage->toArray(false);
                    $imageDetails["imageLocation"] = $assetsHelper->getUrl($imageDetails["imageLocation"], "product");
                    $imageDetails["sizes"]["thumbnail"] = $assetsHelper->getUrl($imageDetails["sizes"]["thumbnail"], "product");
                    $imageDetails["sizes"]["small"] = $assetsHelper->getUrl($imageDetails["sizes"]["small"], "product");
                    $imageDetails["sizes"]["medium"] = $assetsHelper->getUrl($imageDetails["sizes"]["medium"], "product");
                    $imageDetails["sizes"]["large"] = $assetsHelper->getUrl($imageDetails["sizes"]["large"], "product");

                    array_push($images, $imageDetails);
                }
            }
            else{
                $images = array_values($productImages);

                foreach($images as &$image){
                    $image["fullImageLocation"] = $image["imageLocation"];
                    $image["isPrimary"] = false;
                    $image["isDeleted"] = false;
                    $image["defaultLocale"] = $defaultLocale;
                }
            }

            $unitDetails["attributes"] = $attributes;
            $unitDetails["images"] = $images;

            array_push($productUnits, $unitDetails);
        }

        $productVariants = array();
        $translationVariants = array();
        $attributes = $product->getAttributes();

        foreach($attributes as $attributeName){
            if($forTranslation){
                $this->translationService->setDefaultLocale($defaultLocale);
                $attributeName->setLocale($defaultLocale);
                $this->em->refresh($attributeName);
            }

            $variant["id"] = $attributeName->getProductAttributeNameId();
            $variant["name"] = $attributeName->getName();
            $variant["values"] = array();

            $attributeNameValues = array();
            foreach($attributeName->getProductAttributeValues() as $attributeValue){

                if($forTranslation){
                    $attributeValue->setLocale($defaultLocale);
                    $this->em->refresh($attributeValue);
                }

                $id = $attributeValue->getProductAttributeValueId();
                $name = $attributeValue->getValue();

                if(!in_array($name, $attributeNameValues)){
                    $attributeNameValues[$id] = $name;
                }
                else{
                    $index = array_search($name, $attributeNameValues);
                    if($index){
                        unset($attributeNameValues[$index]);
                        $attributeNameValues[$index."-".$id] = $name;
                    }
                }
            }

            foreach($attributeNameValues as $id => $value){
                array_push($variant["values"], array(
                    "id" => (string)$id,
                    "value" => $value
                ));
            }

            array_push($productVariants, $variant);
        }

        $details["hasCombination"] = !empty($attributes)? true : false;
        $details["productUnits"] = $productUnits;

        if($forTranslation && $locale != $defaultLocale){
            foreach($attributes as $attributeName){

                $attributeName->setLocale($locale);
                $this->em->refresh($attributeName);


                foreach($productVariants as $variantKey => $variant){
                    if($variant["id"] == $attributeName->getProductAttributeNameId()){
                        $productVariants[$variantKey]["name"] = $attributeName->getName();
                    }

                    foreach($attributeName->getProductAttributeValues() as $attributeValue){

                        $attributeValue->setLocale($locale);
                        $this->em->refresh($attributeValue);

                        $id = $attributeValue->getProductAttributeValueId();
                        $value = $attributeValue->getValue();

                        foreach($productVariants[$variantKey]["values"] as $valueKey => $variantValue){
                            $ids = explode("-", $variantValue["id"]);

                            if(in_array($id, $ids)){
                                $productVariants[$variantKey]["values"][$valueKey]["value"] = $value;
                            }
                        }
                    }
                }

                array_push($translationVariants, $variant);
            }
        }

        $details["productVariants"] = $productVariants;

        return $details;
    }

    public function updateProductCascade(
        $product,
        $authenticatedUser,
        $options,
        $brand,
        $productGroups,
        $productImages,
        $productUnits
    ){
        $locale = $this->requestStack? $this->requestStack->getCurrentRequest()->getLocale() : "en";

        $product = $this->handleBrand($brand, $product);

        $productGroups = json_decode($productGroups, true);

        if(!empty($productGroups) && $authenticatedUser){
            $product = $this->handleProductGroups($productGroups, $product, $authenticatedUser);
        }

        $productImages = json_decode($productImages, true);

        if(!empty($productImages) && $authenticatedUser){
            $userLanguage = $authenticatedUser->getLanguage();
            $userLanguageCode = $userLanguage && $userLanguage->getLanguageId() ? $userLanguage->getCode(): 'en';

            $product = $this->handleOnUpdateProductImages($productImages, $product, $locale, $userLanguageCode);
        }

        $productUnits = json_decode($productUnits, true);

        if(!empty($productUnits) && $options["user"]){
            $images = array();
            foreach($product->getImages(false, true) as $image){
                $images[$image->getImageLocation(true)] = $image;
            }

            $product = $this->handleOnUpdateProductUnits($productUnits, $images, $product);
        }

        return $product;
    }

    public function createProductCascade(
        $product,
        $authenticatedUser,
        $options,
        $brand,
        $productGroups,
        $productImages,
        $productUnits,
        $isDraft
    ){
        $product = $this->handleBrand($brand, $product);

        $productGroups = json_decode($productGroups, true);

        if(!empty($productGroups) && $authenticatedUser){
            $product = $this->handleProductGroups($productGroups, $product, $authenticatedUser);
        }

        $productImages = json_decode($productImages, true);

        if(!empty($productImages) && $authenticatedUser){
            $language = $authenticatedUser->getLanguage();
            $languageCode = $language && $language->getLanguageId() ? $language->getCode(): 'en';
            $product = $this->handleOncreateProductImages($productImages, $product, $languageCode);
        }

        $productUnits = json_decode($productUnits, true);

        if(!empty($productUnits) && $authenticatedUser){
            $images = array();
            foreach($product->getImages(false, true) as $image){
                $images[$image->getImageLocation(true)] = $image;
            }

            $product = $this->handleOnCreateProductUnits($productUnits, $images, $product);
        }

        $countryCode = $this->translationService->getCountry();
        $country = $this->em->getRepository("YilinkerCoreBundle:Country")
                        ->findOneBy(array(
                            "code" => $countryCode,
                            "status" => Country::ACTIVE_DOMAIN
                        ));

        if($country){
            $productCountry = new ProductCountry;
            $productCountry->setCountry($country)
                           ->setProduct($product)
                           ->setStatus($isDraft? Product::DRAFT : Product::FOR_COMPLETION)
                           ->setDateAdded(Carbon::now())
                           ->setDateLastModified(Carbon::now());

            $this->em->persist($productCountry);
            $product->addProductCountry($productCountry);
        }

        $isDraft? $product->setStatus(Product::DRAFT) : $product->setStatus(Product::FOR_COMPLETION);
        $product->setUser($authenticatedUser);

        return $product;
    }

    private function handleBrand($brand, $product)
    {
        if(is_null($brand)){
            $brand = "";
        }

        $brandRepository = $this->em
                                ->getRepository("YilinkerCoreBundle:Brand");

        $entity = $brandRepository->findOneByName(trim($brand));

        if($entity){
            $product->setBrand($entity);

            foreach($product->getCustomBrand() as $customBrand){
                $product->removeCustomBrand($customBrand);
                $this->em->remove($customBrand);
            }
        }
        else{
            $entity = $brandRepository->find(Brand::CUSTOM_BRAND_ID);

            $customBrand = $this->em->getRepository("YilinkerCoreBundle:CustomBrand")
                                ->findOneBy(array(
                                    "name" => $brand,
                                    "product" => $product
                                ));

            if(!$customBrand){
                $product->setBrand($entity);

                foreach($product->getCustomBrand() as $currentCustomBrand){
                    $product->removeCustomBrand($currentCustomBrand);
                    $this->em->remove($currentCustomBrand);
                }

                $customBrand = new CustomBrand;
                $customBrand->setName((string)$brand);
                $customBrand->setProduct($product);
                $product->addCustomBrand($customBrand);
                $this->em->persist($customBrand);
            }
        }

        return $product;
    }

    private function handleProductGroups($productGroups, $product, $user)
    {
        $userProductGroupRepository = $this->em
                                            ->getRepository("YilinkerCoreBundle:UserProductGroup");

        $persistedUserProductGroups = array();
        $entities = $userProductGroupRepository->findByNamesIn($productGroups, $user);

        foreach($product->getProductGroups() as $productGroup){
            $product->removeProductGroup($productGroup);
            $this->em->remove($productGroup);
        }

        foreach($productGroups as $productGroup){

            $isPersisted = false;
            $userProductGroup = null;
            foreach($entities as $entity){
                if($entity->getName() == trim($productGroup)){
                    $isPersisted = true;
                    $userProductGroup = $entity;
                }
            }

            if(!$isPersisted && !is_null($user)){
                $userProductGroup = new UserProductGroup();
                $userProductGroup->setName($productGroup);
                $userProductGroup->setUser($user);
                $user->addProductGroup($userProductGroup);
            }

            $productGroup = new ProductGroup();
            $productGroup->setProduct($product);
            $productGroup->setUserProductGroup($userProductGroup);
            $product->addProductGroup($productGroup);
        }

        return $product;
    }

    private function handleOncreateProductImages($productImages, $product, $languageCode)
    {
        $product->getImages()->clear();
        foreach ($productImages as $image){
            if(
                is_array($image) &&
                array_key_exists("name", $image) &&
                array_key_exists("isPrimary", $image)
            ){
                $productImage = new ProductImage();
                $productImage->setProduct($product)
                             ->setImageLocation($image["name"])
                             ->setIsPrimary($image["isPrimary"])
                             ->setIsDeleted(false)
                             ->setDefaultLocale($languageCode);

                $product->addImage($productImage);
                $this->em->persist($productImage);
            }
        }

        return $product;
    }

    private function handleOnUpdateProductImages(
        &$productImages,
        $product,
        $targetLocale,
        $userLocale
    ){
        $productImageRepository = $this->em->getRepository("YilinkerCoreBundle:ProductImage");

        $images = array();
        foreach($productImages as $image){
            array_push($images, $image["name"]);
        }

        $removedLocaleImages = $productImageRepository->filterImages($images, $product, $targetLocale, true, true);

        $country = $this->translationService->getCountry();

        foreach($removedLocaleImages as $image){
            $unitImages = $image->getProductUnitImages();
            foreach($unitImages as $unitImage){
                $unit = $unitImage->getProductUnit();
                $unit->setLocale($country);
                $this->em->refresh($unit);
                $unit->removeProductUnitImage($unitImage);
                $this->em->remove($unitImage);
            }

            $product->removeImage($image);
            $this->em->remove($image);
        }

        $this->em->flush();

        $targetLocaleImages = $productImageRepository->filterImages($images, $product, $targetLocale, true, false);
        $userProductImages = $productImageRepository->filterImages($images, $product, $userLocale, true, false);

        foreach($productImages as $key => $image){
            if(
                !array_key_exists($image["name"], $targetLocaleImages) &&
                !array_key_exists($image["name"], $userProductImages)
            ){
                //if not in user locale and target locale : upload & new entity

                $productImage = new ProductImage();
                $productImage->setProduct($product)
                             ->setImageLocation($image["name"])
                             ->setIsPrimary($image["isPrimary"])
                             ->setIsDeleted(false)
                             ->setDefaultLocale($targetLocale);

                $product->addImage($productImage);
                $this->em->persist($productImage);
            }
            elseif(
                !array_key_exists($image["name"], $targetLocaleImages) &&
                array_key_exists($image["name"], $userProductImages)
            ){
                //if in user locale but not in target locale : new entity

                $productImage = new ProductImage();
                $productImage->setProduct($product)
                             ->setImageLocation($image["name"])
                             ->setIsPrimary($image["isPrimary"])
                             ->setIsDeleted(false)
                             ->setDefaultLocale($targetLocale);

                $product->addImage($productImage);
                $this->em->persist($productImage);
                unset($productImages[$key]);
            }
            else{
                //if in user locale and in target locale : remove from product image list on temp
                $productImage = $targetLocaleImages[$image["name"]];

                if($image["isPrimary"] != $productImage->getIsPrimary()){
                    $productImage->setIsPrimary($image["isPrimary"]);
                    $this->em->persist($productImage);
                }

                unset($productImages[$key]);
            }
        }

        return $product;
    }

    private function handleOnCreateProductUnits($productUnits, $productImages, $product)
    {
        $attributes = array();
        $productAttributeNameRepository = $this->em->getRepository("YilinkerCoreBundle:ProductAttributeName");

        foreach($productUnits as $unit){
            if(
                array_key_exists("sku", $unit) &&
                $unit["sku"]
            ){
                /** Unit data */
                $productUnit = new ProductUnit;
                $productUnit->setSku($unit["sku"])
                            ->setQuantity(0)
                            ->setPrice(0.00)
                            ->setDiscountedPrice(0.00)
                            ->setCommission(0.00)
                            ->setDateCreated(Carbon::now())
                            ->setDateLastModified(Carbon::now())
                            ->setWeight($unit["weight"])
                            ->setHeight($unit["height"])
                            ->setWidth($unit["width"])
                            ->setLength($unit["length"])
                            ->setProduct($product);


                $product->addUnit($productUnit);
                $this->em->persist($productUnit);

                /** Attribute data */
                if(array_key_exists("attributes", $unit)){
                    foreach($unit["attributes"] as $attribute){
                        $productAttribute = $productAttributeNameRepository->findOneBy(array(
                                                "name" => $attribute["name"],
                                                "product" => $product
                                            ));
                        if(
                            !array_key_exists($attribute["name"], $attributes) &&
                            !$productAttribute
                        ){
                            $productAttributeName = new ProductAttributeName();
                            $productAttributeName->setName($attribute["name"]);
                            $productAttributeName->setProduct($product);

                            $product->addAttribute($productAttributeName);
                            $this->em->persist($productAttributeName);

                            $attributes[$attribute["name"]] = $productAttributeName;
                        }
                        elseif($productAttribute){
                            $attributes[$attribute["name"]] = $productAttribute;
                        }

                        $productAttributeValue = new ProductAttributeValue();
                        $productAttributeValue->setProductAttributeName($attributes[$attribute["name"]]);
                        $productAttributeValue->setProductUnit($productUnit);
                        $productAttributeValue->setValue($attribute["value"]);

                        $productUnit->addProductAttributeValue($productAttributeValue);
                        $this->em->persist($productAttributeValue);
                    }
                }

                /** Image data */
                if(sizeof($productUnits) > 1){
                    if(array_key_exists("images", $unit)){
                        foreach ($unit["images"] as $image) {
                            if(array_key_exists($image["name"], $productImages)){
                                $productUnitImage = new ProductUnitImage();
                                $productUnitImage->setProductUnit($productUnit);
                                $productUnitImage->setProductImage($productImages[$image["name"]]);

                                $productUnit->addProductUnitImage($productUnitImage);
                                $this->em->persist($productUnitImage);
                            }
                        }
                    }
                }
                else{
                    foreach($productImages as $productImage){
                        $productUnitImage = new ProductUnitImage();
                        $productUnitImage->setProductUnit($productUnit);
                        $productUnitImage->setProductImage($productImage);

                        $productUnit->addProductUnitImage($productUnitImage);
                        $this->em->persist($productUnitImage);
                    }
                }
            }
        }

        return $product;
    }

    private function handleOnUpdateProductUnits($productUnits, $productImages, $product)
    {
        $productUnitRepository = $this->em->getRepository("YilinkerCoreBundle:ProductUnit");

        $images = array();
        foreach($product->getImages(false, true) as $image){
            $images[$image->getImageLocation(true)] = $image;
        }

        $attributes = array();
        if(sizeof($productUnits) > 0){

            $localeProductUnits = $this->constructLocaleProductUnits($product->getUnits());
            $unitsToUpdate = array();
            $unitsToDelete = array();

            foreach($localeProductUnits as $localeProductUnitKey => $localeProductUnit){
                foreach($productUnits as $productUnitKey => $productUnit){
                    if(
                        !array_key_exists("attributes", $productUnit) &&
                        empty($localeProductUnit["attributes"])
                    ){
                        $this->handleUnitRelationship(
                            $unitsToUpdate,
                            $productUnits,
                            $localeProductUnits,
                            $localeProductUnitKey,
                            $productUnitKey
                        );
                    }
                    elseif(
                        array_key_exists("attributes", $productUnit) &&
                        is_array($productUnit["attributes"]) &&
                        empty($productUnit["attributes"]) &&
                        empty($localeProductUnit["attributes"])
                    ){
                        $this->handleUnitRelationship(
                            $unitsToUpdate,
                            $productUnits,
                            $localeProductUnits,
                            $localeProductUnitKey,
                            $productUnitKey
                        );
                    }
                    elseif(
                        array_key_exists("attributes", $productUnit) &&
                        sizeof($productUnit["attributes"]) ==
                        sizeof($localeProductUnit["attributes"])
                    ){
                        $tmpLocaleAttributes = $localeProductUnit["attributes"];
                        foreach($productUnit["attributes"] as $attribute){
                            if(in_array($attribute, $tmpLocaleAttributes)){
                                $index = array_search($attribute, $tmpLocaleAttributes);
                                unset($tmpLocaleAttributes[$index]);
                            }
                        }

                        if(empty($tmpLocaleAttributes)){
                            $this->handleUnitRelationship(
                                $unitsToUpdate,
                                $productUnits,
                                $localeProductUnits,
                                $localeProductUnitKey,
                                $productUnitKey
                            );
                        }
                    }
                }
            }

            // at this point :
            // $unitsToUpdate = units to update
            // $unitsToDelete = units to delete
            // $productUnits = units to be created

            foreach($localeProductUnits as $localeProductUnit){
                array_push($unitsToDelete, $localeProductUnit["productUnitId"]);
            }

            $productUnitRepository = $this->em->getRepository("YilinkerCoreBundle:ProductUnit");
            $archivedUnits = $productUnitRepository->loadProductUnitsIn($unitsToDelete);

            //delete
            $product = $this->removeUnitRelationships($archivedUnits, $product);

            //create
            $product = $this->handleOnCreateProductUnits($productUnits, $images, $product);

            $unitIdsToUpdate = array();
            foreach($unitsToUpdate as $unitToUpdate){
                array_push($unitIdsToUpdate, $unitToUpdate["productUnitId"]);
            }

            //update
            $productUnitsToUpdate = $productUnitRepository->loadProductUnitsIn($unitIdsToUpdate);
            $country = $this->translationService->getCountry();

            foreach($unitsToUpdate as $unitToUpdate){
                $unit = $productUnitsToUpdate[$unitToUpdate["productUnitId"]];

                $this->translationService->setCountry($country);
                $unit->setLocale($country);
                $this->em->refresh($unit);

                $unit->setSku($unitToUpdate["sku"])
                     ->setLength((string)$unitToUpdate["length"])
                     ->setWidth((string)$unitToUpdate["width"])
                     ->setHeight((string)$unitToUpdate["height"])
                     ->setWeight((string)$unitToUpdate["weight"]);

                if(
                    array_key_exists("images", $unitToUpdate) &&
                    !empty($unitToUpdate["images"])
                ){
                    $currentUnitImages = $unit->getProductUnitImages();
                    foreach($currentUnitImages as $image){
                        $hasMatch = false;
                        $imageLocation = $image->getProductImage()->getImageLocation(true);
                        foreach($unitToUpdate["images"] as $unitImage){
                            if($unitImage["name"] == $imageLocation){
                                $hasMatch = true;
                            }
                        }

                        if(!$hasMatch){
                            $unit->removeProductUnitImage($image);
                            $this->em->remove($image);
                        }
                    }

                    foreach ($unitToUpdate["images"] as $image){
                        $hasMatch = false;
                        foreach($currentUnitImages as $currentUnitImage){
                            $imageLocation = $currentUnitImage->getProductImage()->getImageLocation(true);
                            if($image["name"] == $imageLocation){
                                $hasMatch = true;
                            }
                        }

                        if(
                            array_key_exists($image["name"], $productImages) &&
                            !$hasMatch
                        ){
                            $productUnitImage = new ProductUnitImage();
                            $productUnitImage->setProductUnit($unit)
                                             ->setProductImage($productImages[$image["name"]]);

                            $productImages[$image["name"]]->addProductUnitImage($productUnitImage);
                            $unit->addProductUnitImage($productUnitImage);
                            $this->em->persist($productUnitImage);
                        }
                    }
                }
            }
        }

        return $product;
    }

    private function removeUnitRelationships($archivedUnits, $product)
    {
        foreach($archivedUnits as $archivedUnit){

            foreach($archivedUnit->getProductUnitImages() as $unitImage){

                $productImage = $unitImage->getProductImage();

                $productImage->removeProductUnitImage($unitImage);
                $archivedUnit->removeProductUnitImage($unitImage);

                $this->em->remove($unitImage);
                $this->em->flush();
            }

            foreach($archivedUnit->getProductAttributeValues() as $attributeValue){

                $productAttributeName = $attributeValue->getProductAttributeName();

                $productAttributeName->removeProductAttributeValue($attributeValue);
                $archivedUnit->removeProductAttributeValue($attributeValue);

                $this->em->remove($attributeValue);
                $this->em->flush();

                if(!$productAttributeName->getProductAttributeValues()->count()){
                    $product->removeAttribute($productAttributeName);
                    $this->em->remove($productAttributeName);
                    $this->em->flush();
                }
            }

            foreach($archivedUnit->getProductUnitWarehouses() as $unitWarehouse){

                $userWarehouse = $unitWarehouse->getUserWarehouse();

                $userWarehouse->removeProductUnitWarehouse($unitWarehouse);
                $archivedUnit->removeProductUnitWarehouse($unitWarehouse);

                $this->em->remove($unitWarehouse);
                $this->em->flush();
            }

            foreach($archivedUnit->getProductPromoMaps() as $promoMap){

                $promoInstance = $promoMap->getPromoInstance();

                $promoInstance->removeProductPromoMap($promoMap);
                $archivedUnit->removeProductPromoMap($promoMap);

                $this->em->remove($promoMap);
                $this->em->flush();
            }

            $manufacturerProductUnitMap = $archivedUnit->getManufacturerProductUnitMap();

            if($manufacturerProductUnitMap){

                $this->em->remove($manufacturerProductUnitMap);
                $this->em->flush();
            }

            $product->removeUnit($archivedUnit);
            $this->em->remove($archivedUnit);
            $this->em->flush();
        }

        return $product;
    }

    private function handleUnitRelationship(
        &$unitsToUpdate,
        &$productUnits,
        &$localeProductUnits,
        $localeProductUnitKey,
        $productUnitKey
    ){
        $productUnits[$productUnitKey]["productUnitId"] =
        $localeProductUnits[$localeProductUnitKey]["productUnitId"];

        array_push($unitsToUpdate, $productUnits[$productUnitKey]);

        unset($localeProductUnits[$localeProductUnitKey]);
        unset($productUnits[$productUnitKey]);
    }

    private function constructLocaleProductUnits($productUnits)
    {
        $localeProductUnits = array();
        foreach($productUnits as $productUnit){
            $unitDetails = array();
            $unitDetails["productUnitId"] = $productUnit->getProductUnitId();
            $unitDetails["attributes"] = array();
            $unitAttributes = $productUnit->getProductAttributeValues();
            if($unitAttributes->count() > 0){
                foreach($unitAttributes as $unitAttribute){
                    array_push($unitDetails["attributes"], array(
                        "name" => $unitAttribute->getProductAttributeName()->getName(),
                        "value" => $unitAttribute->getValue()
                    ));
                }
            }

            $unitDetails["images"] = array();
            foreach($productUnit->getProductUnitImages() as $unitImage){
                array_push($unitDetails["images"], array(
                    "name" => $unitImage->getProductImage()->getImageLocation(true)
                ));
            }

            $unitDetails["sku"] = $productUnit->getSku();
            $unitDetails["length"] = $productUnit->getLength();
            $unitDetails["width"] = $productUnit->getWidth();
            $unitDetails["weight"] = $productUnit->getWeight();
            $unitDetails["height"] = $productUnit->getHeight();
            $unitDetails["isActive"] = $productUnit->getStatus() == ProductUnit::STATUS_ACTIVE? true : false;

            array_push($localeProductUnits, $unitDetails);
        }

        return $localeProductUnits;
    }

    public function apiTranslateProduct($product, $images, $variants, $targetLocale)
    {
        $defaultLocale = $product->getDefaultLocale();
        $product = $this->handleOnUpdateProductImages($images, $product, $targetLocale, $defaultLocale);

        $attributes = $product->getAttributes();
        foreach($attributes as $attributeName){
            foreach ($variants as $variant){
                if($attributeName->getProductAttributeNameId() == $variant["id"]){
                    $attributeName->setLocale($targetLocale);
                    $attributeName->setName($variant["name"]);

                    $attributeValues = $attributeName->getProductAttributeValues();
                    foreach($attributeValues as $attributeValue){
                        foreach($variant["values"] as $value){
                            $valueIds = explode("-", $value["id"]);
                            if(in_array($attributeValue->getProductAttributeValueId(), $valueIds)){
                                $attributeValue->setLocale($targetLocale);
                                $attributeValue->setValue($value["value"]);
                            }
                        }
                    }
                }
            }
        }

        $this->em->flush();
        return $product;
    }

    /**
     * Update product units (bulk)
     *
     * @param Product $product
     * @param array $productUnits
     * @return bool
     */
    public function updateProductUnits(Product $product, array $productUnits, $productImageEntityContainer)
    {
        $productUnitRepository = $this->em->getRepository('YilinkerCoreBundle:ProductUnit');
        $this->em->beginTransaction();
        $productUnitContainer = array();
        try {
            foreach ($productUnits as $productUnit) {
                $productUnitEntity = isset($productUnit['productUnitId']) ? $productUnitRepository->find($productUnit['productUnitId']) : null;

                if ($productUnitEntity instanceof ProductUnit) {
                    $productUnitEntity->setDateLastModified(Carbon::now());
                    $productUnitEntity->setSku($productUnit['sku']);
                    $productUnitEntity->setLength($productUnit['unitLength']);
                    $productUnitEntity->setHeight($productUnit['unitHeight']);
                    $productUnitEntity->setWeight($productUnit['unitWeight']);
                    $productUnitEntity->setWidth($productUnit['unitWidth']);
                }
                else {
                    $productUnitEntity = $this->addProductUnit(
                        $product,
                        0,
                        $productUnit['sku'],
                        0,
                        0,
                        $productUnit['unitWeight'],
                        $productUnit['unitLength'],
                        $productUnit['unitWidth'],
                        $productUnit['unitHeight'],
                        ProductUnit::STATUS_ACTIVE
                    );

                    if (isset($productUnit['hasNoCombination']) && $productUnit['hasNoCombination'] == true) {
                        continue;
                    }

                    foreach ($productUnit['images'] as $image) {
                        $this->addProductUnitImage($productUnitEntity, $productImageEntityContainer[$image['name']]);
                    }

                    foreach ($productUnit['attributes'] as $productAttribute) {
                        $productAttributeName = $productAttribute['name'];
                        $productAttributeValue = $productAttribute['value'];

                        if (isset($productAttributeContainer[$productAttributeName])) {
                            $productAttributeEntity = $productAttributeContainer[$productAttributeName];
                        }
                        else {
                            $productAttributeEntity = $this
                                ->addProductAttributeName(
                                    $product,
                                    $productAttributeName
                                );
                            $productAttributeContainer[$productAttributeName] = $productAttributeEntity;
                        }

                        $this->addProductAttributeValue(
                            $productAttributeEntity,
                            $productUnitEntity,
                            $productAttributeValue
                        );

                    }

                    $this->em->persist($productUnitEntity);
                }

                $productUnitContainer[] = $productUnitEntity;
            }

            if (count($productUnitContainer) > 0) {
                $unUsedProductUnit = $productUnitRepository->findByNot($product, 'productUnitId', $productUnitContainer);

                if (count($unUsedProductUnit) > 0) {
                    $this->removeProductUnits($unUsedProductUnit);
                }
            }

            $this->em->flush();
            $this->em->commit();
            $isSuccessful = true;
        }
        catch (\Exception $e) {
            $this->em->rollback();
            $isSuccessful = false;
        }

        return $isSuccessful;
    }

}
