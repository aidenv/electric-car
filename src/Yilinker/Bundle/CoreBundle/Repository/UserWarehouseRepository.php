<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse;

/**
 * Class CartRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserWarehouseRepository extends EntityRepository
{
    public function getUserWarehouses($user)
    {
        return $this->qb()->filterBy(array(
            'user' => $user,
            'isDelete' => false
        ));
    }

    public function filterBy(array $filter)
    {
        if (isset($filter['user'])) {
            $this->getQB()->andWhere('this.user = :user')
                          ->setParameter('user', $filter['user']);
        }

        if (isset($filter['isDelete'])) {
            $this->getQB()->andWhere('this.isDelete = :isDelete')
                          ->setParameter('isDelete', (bool) $filter['isDelete']);
        }

        return $this;
    }

    public function updateProductUnitWarehouse($warehouse, $updateData = array())
    {
        $productUnitIds = array_keys($updateData);

        $tbProductUnitWarehouse = $this->_em->getRepository('YilinkerCoreBundle:ProductUnitWarehouse');
        $unitWarehouses = $tbProductUnitWarehouse
            ->findBy(
                array(
                    'userWarehouse' => $warehouse,
                    'productUnit' => $productUnitIds
                )
            )
        ;

        $editIds = array();
        foreach ($unitWarehouses as $unitWarehouse) {
            $productUnitId = $unitWarehouse->getProductUnit()->getProductUnitId();
            $quantity = $updateData[$productUnitId];
            $unitWarehouse->setQuantity($quantity);
            $editIds[] = $productUnitId;
        }

        $addIds = array_diff($productUnitIds, $editIds);
        foreach ($addIds as $productUnitId) {
            $quantity = $updateData[$productUnitId];
            $productUnit = $this->_em->getReference('YilinkerCoreBundle:ProductUnit', $productUnitId);

            $unitWarehouse = new ProductUnitWarehouse;
            $unitWarehouse
                ->setUserWarehouse($warehouse)
                ->setProductUnit($productUnit)
                ->setQuantity($quantity)
            ;
            $this->_em->persist($unitWarehouse);
        }

        $this->_em->flush();
    }
}
