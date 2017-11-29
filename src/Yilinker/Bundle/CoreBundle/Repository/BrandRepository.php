<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\Brand;

/**
 * Class BrandRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class BrandRepository extends EntityRepository
{

    /**
     * Search for possible Brand by Brand Name
     * @param string $brandKeyword
     * @param int $limit
     * @param boolean $returnQB
     * @param boolean
     * @param $excludedBrandIds
     * @return array
     */
    public function getBrandByName ($brandKeyword, $limit = 10, $returnQB = false, $includeCustomBrand = false, $excludedBrandIds = null)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $query = $qb->select('brand')
                    ->from('Yilinker\Bundle\CoreBundle\Entity\Brand', 'brand')
                    ->where('brand.name LIKE :brandKeyword')
                    ->setParameter(':brandKeyword', '%' . $brandKeyword . '%', \PDO::PARAM_STR)
                    ->setMaxResults($limit);

        if ($includeCustomBrand === false) {
            $qb->andWhere('brand.brandId != :customBrand')
               ->setParameter(':customBrand', Brand::CUSTOM_BRAND_ID, \PDO::PARAM_INT);
        }

        if (!is_null($excludedBrandIds) && is_array($excludedBrandIds)) {
            $query->andWhere($qb->expr()->notIn('brand.brandId', implode(',', $excludedBrandIds)));
        }

        if ($returnQB) {
            return $query;
        }
        $query = $query->getQuery();

        return $query->getResult();
    }

    /**
     * Retrieve brands by brandIds
     *
     * @paramt int[] $brandIds
     * @return Brands[]
     */
    public function getBrandsByIds($brandIds)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $query = $qb->select('brand')
                    ->from('Yilinker\Bundle\CoreBundle\Entity\Brand', 'brand')
                    ->where('brand.brandId IN (:brandIds)')
                    ->setParameter(':brandIds', $brandIds)
                    ->getQuery();

        return $query->getResult();
    }

    /**
     * Retrieve brands by reference number
     *
     * @paramt string[] $referenceNumbers
     * @return Brands[]
     */
    public function findByReferenceNumbers($referenceNumbers)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $query = $qb->select('brand')
                    ->from('Yilinker\Bundle\CoreBundle\Entity\Brand', 'brand')
                    ->where('brand.referenceNumber IN (:referenceNumbers)')
                    ->setParameter(':referenceNumbers', $referenceNumbers)
                    ->getQuery();

        return $query->getResult();
    }

    public function getNonCustomBrands()
    {
        return $this->createQueryBuilder("b")
                    ->where("b.brandId <> :customBrand")
                    ->setParameter(":customBrand", Brand::CUSTOM_BRAND_ID)
                    ->getQuery()
                    ->getResult();
    }
}
