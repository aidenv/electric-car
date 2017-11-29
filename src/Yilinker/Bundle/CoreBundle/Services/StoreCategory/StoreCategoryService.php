<?php

namespace Yilinker\Bundle\CoreBundle\Services\StoreCategory;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\StoreCategory;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;

/**
 * Class StoreCategoryService
 * @package Yilinker\Bundle\CoreBundle\Services\StoreCategory
 */
class StoreCategoryService
{

    /**
     * Doctrine entity manager
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Constructor
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     */
    public function __construct($entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Get Category with Selected Store Category
     *
     * @param Store $store
     * @return array
     */
    public function getCategoryWithSelectedStoreCategory (Store $store)
    {
        $response = array();
        $storeCategoryArray = array();
        $productCategories = $this->em->getRepository('YilinkerCoreBundle:ProductCategory')
                                  ->findBy(array(
                                      'parent'   => ProductCategory::ROOT_CATEGORY_ID,
                                      'isDelete' => false,
                                  ));
        $storeCategories = $this->em->getRepository('YilinkerCoreBundle:StoreCategory')->findByStore($store);
        $selectedCount = 0;

        if (sizeof($productCategories) > 0) {

            foreach ($productCategories as $productCategory) {
                $productCategoryId = (int) $productCategory->getProductCategoryId();
                $isSelected = false;

                if (sizeof($storeCategories) > 0) {

                    foreach ($storeCategories as $storeCategory) {
                        $storeProductCategoryId = (int) $storeCategory->getProductCategory()->getProductCategoryId();

                        if ($storeProductCategoryId === $productCategoryId) {
                            $isSelected = true;
                            $selectedCount++;
                            break;
                        }

                    }

                }

                if ($productCategoryId !== 1) {
                    $storeCategoryArray[] = array (
                        'productCategory' => $productCategory,
                        'isSelected' => $isSelected
                    );
                }

            }

            $response = array (
                'data' => $storeCategoryArray,
                'hasSelected' => $selectedCount > 0 ? true : false
            );

        }

        return $response;
    }

    /**
     * Create Store Category
     *
     * @param Store $store
     * @param ProductCategory $productCategory
     * @return StoreCategory
     */
    public function createStoreCategory (Store $store, ProductCategory $productCategory)
    {
        $storeCategory = new StoreCategory();
        $storeCategory->setStore($store);
        $storeCategory->setProductCategory($productCategory);
        $storeCategory->setDateAdded(Carbon::now());
        $storeCategory->setDateLastModified(Carbon::now());

        $this->em->persist($storeCategory);
        $this->em->flush();

        return $storeCategory;
    }

    /**
     * Remove Store Category
     *
     * @param StoreCategory $storeCategory
     */
    public function deleteStoreCategory (StoreCategory $storeCategory)
    {
        $this->em->remove($storeCategory);
        $this->em->flush();
    }

    /**
     * Remove existing Store Category and create new one
     *
     * @param Store $store
     * @param $productCategories
     * @return bool
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function processSelectedCategory (Store $store, $productCategories)
    {
        $this->em->getConnection()->beginTransaction();
        $storeCategoryEntities = $this->em->getRepository('YilinkerCoreBundle:StoreCategory')->findByStore($store);
        try {

            foreach ($storeCategoryEntities as $storeCategoryEntity) {
                $this->deleteStoreCategory($storeCategoryEntity);
            }

            foreach ($productCategories as $productCategoryId) {
                $productCategoryEntity = $this->em->getRepository('YilinkerCoreBundle:ProductCategory')->find($productCategoryId);
                $this->createStoreCategory ($store, $productCategoryEntity);
            }

            $this->em->flush();
            $this->em->getConnection()->commit();
        }
        catch (\Exception $e) {
            $this->em->getConnection()->rollback();
        }

        return true;
    }

}
