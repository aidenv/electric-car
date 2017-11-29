<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Transformers;

use Doctrine\ORM\EntityManager;
use FOS\ElasticaBundle\Transformer\ModelToElasticaTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Elastica\Document;
use Yilinker\Bundle\CoreBundle\Entity\Product;

class StoreToElasticaTransformer implements ModelToElasticaTransformerInterface
{
    /**
     * Symfony service container
     */
    private $serviceContainer;

    /**
     * Set the entityManager
     *
     */
    public function setServiceContainer($serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * Transforms an store into an elastica object having the required keys
     *
     * @param Store $oject the object to convert
     * @param array $fields
     *
     * @return Document
     */
    public function transform($object, array $fields)
    {
        $em = $this->serviceContainer->get('doctrine.orm.entity_manager');
        
        $store = $object;
        $identifier = $store->getStoreId();
        $productCategoryRepository = $em->getRepository("YilinkerCoreBundle:ProductCategory");
        $storeRepository = $em->getRepository("YilinkerCoreBundle:Store");
        $specialtyCategory = $productCategoryRepository->getUserSpecialty($store->getUser());

        $specialtyCategory = $specialtyCategory ? array( 
            'id' => $specialtyCategory->getProductCategoryId(),
            'name' => $specialtyCategory->getName(),
        ) : array();

        $user = $store->getUser();
        $activeProductCount = $storeRepository->getStoreProductCount($store, array(Product::ACTIVE));

        $values = array(
            'id'                  => $identifier,
            'specialtyCategory'   => $specialtyCategory,
            'storeName'           => $store->getStoreName(),
            'storeDescription'    => $store->getStoreDescription(),
            'storeSlug'           => $store->getStoreSlug(),
            'accreditationLevel'  => $store->getAccreditationLevel() ? 
                                     $store->getAccreditationLevel()->getAccreditationLevelId() : 0,
            'slugChanged'         => $store->getSlugChanged(),
            'hasCustomCategory'   => $store->getHasCustomCategory(),
            'storeType'           => $store->getStoreType(),
            'dateAdded'           => $user->getDateAdded()->format('Y-m-d'),
            'productCount'        => $activeProductCount,
        );

        //Create a document to index
        $document = new Document($identifier, $values);

        return $document;
    }
}
