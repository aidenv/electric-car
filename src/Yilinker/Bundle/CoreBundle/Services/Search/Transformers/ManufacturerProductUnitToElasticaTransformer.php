<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Transformers;

use Doctrine\ORM\EntityManager;
use FOS\ElasticaBundle\Transformer\ModelToElasticaTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Elastica\Document;
use DateTime;


class ManufacturerProductUnitToElasticaTransformer implements ModelToElasticaTransformerInterface
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
     * Transforms an manufacturer product unit into an elastica object having the required keys
     *
     * @param Store $oject the object to convert
     * @param array $fields
     *
     * @return Document
     */
    public function transform($object, array $fields)
    {
        $em = $this->serviceContainer->get('doctrine.orm.entity_manager');
        
        $identifier = $object->getManufacturerProductUnitId();
        $manufacturerProduct = $object->getManufacturerProduct();
        $reviewCount = $em->getRepository('YilinkerCoreBundle:ManufacturerProductUnit')
                          ->getManufacturerProductUnitReviewCount($object);
                
        $values = array(
            'id'                           => $identifier,
            'manufacturerProductUnitId'    => $identifier,
            'sku'                          => $object->getSku(),
            'dateCreated'                  => $object->getDateCreated()->format(DateTime::ISO8601),
            'dateLastModified'             => $object->getDateLastModified()->format(DateTime::ISO8601),
            'status'                       => $object->getStatus(),
            'weight'                       => $object->getWeight(),
            'height'                       => $object->getHeight(),
            'width'                        => $object->getWidth(),
            'length'                       => $object->getLength(),
            'isInventoryConfirmed'         => $object->getIsInventoryConfirmed(),
            'moq'                          => $object->getMoq(),
            'quantity'                     => $object->getQuantity(),
            'price'                        => $object->getPrice(),
            'discountedPrice'              => $object->getDiscountedPrice(),
            'unitPrice'                    => $object->getUnitPrice(),
            'referenceNumber'              => $object->getReferenceNumber(),
            'visitCount'                   => $manufacturerProduct->getProductPageViews(),
            'wishlistCount'                => $manufacturerProduct->getFavoriteCount(),
            'averageRating'                => $manufacturerProduct->getRating(),
            'storeCount'                   => $manufacturerProduct->getSellerCount(),
            'flattenedCategory'            => $manufacturerProduct->getFlattenedCategory(),
            'flattenedManufacturer'        => $manufacturerProduct->getFlattenedManufacturer(),
            'reviewCount'                  => $reviewCount,
        );

        //Create a document to index
        $document = new Document($identifier, $values);

        return $document;
    }

}
