<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Transformers;

use FOS\ElasticaBundle\Doctrine\AbstractElasticaToModelTransformer;
use Doctrine\ORM\Query;

class ElasticaToManufacturerProductUnitTransformer extends AbstractElasticaToModelTransformer
{
    /**
     * Fetch objects for theses identifier values
     *
     * @param array $identifierValues ids values
     * @param Boolean $hydrate whether or not to hydrate the objects, false returns arrays
     * @return array of objects or arrays
     */
    protected function findByIdentifiers(array $identifierValues, $hydrate)
    {
        if (empty($identifierValues)) {
            return array();
        }

        $hydrationMode = Query::HYDRATE_ARRAY;

        $qb = $this->registry
                   ->getManagerForClass('YilinkerCoreBundle:ManufacturerProductUnit')
                   ->getRepository('YilinkerCoreBundle:ManufacturerProductUnit')
                   ->createQueryBuilder('mpu')
                   ->select('mpu');
        $qb->where(
            $qb->expr()->in('mpu.manufacturerProductUnitId', ':values')
        )->setParameter('values', $identifierValues);

        return $qb->getQuery()->execute();
    }

    public function transform(array $elasticaObjects)
    {
        $ids = $highlights = array();
        foreach ($elasticaObjects as $elasticaObject) {
            $ids[] = $elasticaObject->getId();
            $highlights[$elasticaObject->getId()] = $elasticaObject->getHighlights();
        }

        $objects = $this->findByIdentifiers($ids, $this->options['hydrate']);
        if (!$this->options['ignore_missing'] && count($objects) < count($elasticaObjects)) {
            throw new \RuntimeException('Cannot find corresponding Doctrine objects for all Elastica results.');
        }

        foreach ($objects as $object) {
            if ($object instanceof HighlightableModelInterface) {
                $object->setElasticHighlights($highlights[$object->getId()]);
            }

            foreach ($elasticaObjects as $elasticaObject) {
                $source = $elasticaObject->getSource();
                if($source["manufacturerProductUnitId"] == $object->getManufacturerProductUnitId()){
                    if(array_key_exists("reviewCount", $source)){
                        $object->setReviewCount($source["reviewCount"]);
                    }
                    if(array_key_exists("visitCount", $source)){
                        $object->setViewCount($source["visitCount"]);
                    }
                    if(array_key_exists("wishlistCount", $source)){
                        $object->setWishlistCount($source["wishlistCount"]);
                    }
                    if(array_key_exists("storeCount", $source)){
                        $object->setStoreCount($source["storeCount"]);
                    }
                    if(array_key_exists("referenceNumber", $source)){
                        $object->setReferenceNumber($source["referenceNumber"]);
                    }
                    if(array_key_exists("averageRating", $source)){
                        $object->setAverageRating($source["averageRating"]);
                    }
                    
                    
                    break;
                }
            }
        }

        // sort objects in the order of ids
        $idPos = array_flip($ids);
        $identifier = $this->options['identifier'];
        usort($objects, $this->getSortingClosure($idPos, $identifier));

        return $objects;
    }
}
