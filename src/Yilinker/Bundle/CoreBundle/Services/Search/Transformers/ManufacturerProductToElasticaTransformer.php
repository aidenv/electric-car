<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Transformers;

use DateTime;
use Doctrine\ORM\EntityManager;
use FOS\ElasticaBundle\Transformer\ModelToElasticaTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Elastica\Document;


class ManufacturerProductToElasticaTransformer implements ModelToElasticaTransformerInterface
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
        
        $identifier = $object->getManufacturerProductId();
        $unit = $object->getDefaultUnit() ? $object->getDefaultUnit() : $object->getFirstUnit();

        $values = array(
            'id'                           => $identifier,
            'manufacturerProductId'        => $identifier,
            'name'                         => $object->getName(),
            'dateAdded'                    => $object->getDateAdded()->format(DateTime::ISO8601),
            'status'                       => $object->getStatus(),
            'price'                        => $unit ? $unit->getPrice() : "0.0000",
            'discountedPrice'              => $unit ? $unit->getDiscountedPrice() : "0.0000",
            'visitCount'                   => $object->getProductPageViews(),
            'flattenedCategory'            => $object->getFlattenedCategory(),
            'flattenedManufacturer'        => $object->getFlattenedManufacturer(),
            'flattenedBrand'               => $object->getFlattenedBrand(),
        );

        //Create a document to index
        $document = new Document($identifier, $values);

        return $document;
    }

}
