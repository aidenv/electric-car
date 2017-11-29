<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Transformers;

use FOS\ElasticaBundle\Transformer\ModelToElasticaTransformerInterface;
use Elastica\Document;
use DateTime;

class InhouseProductUserToElasticaTransformer implements ModelToElasticaTransformerInterface
{
    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function transform($inhouseProductUser, array $fields)
    {
        $data = array(
            'inhouseProductUserId' => $inhouseProductUser->getInhouseProductUserId(),
            'status'               => $inhouseProductUser->getStatus(),
            'dateAdded'            => $inhouseProductUser->getDateAdded()->format(DateTime::ISO8601),
            'dateLastModified'     => $inhouseProductUser->getDateLastModified()->format(DateTime::ISO8601),
            'flattenedAffiliate'   => $inhouseProductUser->getFlattenedAffiliate()
        );

        $document = new Document($inhouseProductUser->getInhouseProductUserId(), $data);
        $product = $inhouseProductUser->getProduct();
        if(!$product){
            dump($inhouseProductUser);exit;
        }
        $document->setParent($product->getProductId());

        return $document;
    }
}