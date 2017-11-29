<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Transformers;

use FOS\ElasticaBundle\Doctrine\AbstractElasticaToModelTransformer;
use Doctrine\ORM\Query;

class ElasticaToManufacturerProductTransformer extends AbstractElasticaToModelTransformer
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
                   ->getManagerForClass('YilinkerCoreBundle:ManufacturerProduct')
                   ->getRepository('YilinkerCoreBundle:ManufacturerProduct')
                   ->createQueryBuilder('mp')
                   ->select('mp');
        $qb->where(
            $qb->expr()->in('mp.manufacturerProductId', ':values')
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
                if($source["manufacturerProductId"] == $object->getManufacturerProductId()){
                    if(array_key_exists("visitCount", $source)){
                        $object->setProductPageViews($source["visitCount"]);
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
