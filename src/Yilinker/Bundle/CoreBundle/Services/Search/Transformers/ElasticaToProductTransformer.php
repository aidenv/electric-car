<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Transformers;

use FOS\ElasticaBundle\Doctrine\AbstractElasticaToModelTransformer;
use Doctrine\ORM\Query;

class ElasticaToProductTransformer extends AbstractElasticaToModelTransformer
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
                   ->getManagerForClass('YilinkerCoreBundle:Product')
                   ->getRepository('YilinkerCoreBundle:Product')
                   ->createQueryBuilder('p')
                   ->select('p');
        $qb->where(
            $qb->expr()->in('p.productId', ':values')
        )->setParameter('values', $identifierValues);

        return $qb->getQuery()
                  ->useResultCache(true, 3600)
                  ->execute();
    }

    public function transform(array $elasticaObjects)
    {
        $ids = $highlights = array();
        $elasticaByID = array();
        foreach ($elasticaObjects as $elasticaObject) {
            $ids[] = $elasticaObject->getId();
            $highlights[$elasticaObject->getId()] = $elasticaObject->getHighlights();
            $elasticaByID[$elasticaObject->getId()] = $elasticaObject;
        }

        $objects = $this->findByIdentifiers($ids, $this->options['hydrate']);
        if (!$this->options['ignore_missing'] && count($objects) < count($elasticaObjects)) {
            throw new \RuntimeException('Cannot find corresponding Doctrine objects for all Elastica results.');
        }

        foreach ($objects as $object) {
            $object->elastica['internationalWarehouses'] = array();
            $object->elastica['warehouses'] = $elasticaByID[$object->getProductId()]->getSource()['warehouses'];
            foreach ($object->elastica['warehouses'] as $warehouse) {
                $countryCodes = explode('-', $warehouse);
                $countryFrom = array_shift($countryCodes);
                $countryWarehouse = array_shift($countryCodes);
                if ($object->getCountryCode() == $countryFrom && $countryFrom != $countryWarehouse) {
                    $object->elastica['internationalWarehouses'][] = $countryWarehouse;
                }
            }
            if ($object instanceof HighlightableModelInterface) {
                $object->setElasticHighlights($highlights[$object->getId()]);
            }

            foreach ($elasticaObjects as $elasticaObject) {
                $source = $elasticaObject->getSource();
                if($source["productId"] == $object->getProductId()){
                    if(array_key_exists("wishlistCount", $source)){
                        $object->setWishlistCount($source["wishlistCount"]);
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
