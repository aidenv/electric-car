<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\CustomBrand;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductAttributeName;
use Yilinker\Bundle\CoreBundle\Entity\ProductAttributeValue;
use Yilinker\Bundle\CoreBundle\Entity\ProductCountry;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnitImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse;
use Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Gedmo\Translatable\Entity\Translation;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class StatusListener
{
    private $container;
    private $args;
    private $event;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->args = $args;
        $this->event = 'prePersist';
        $this->adjustStatus();
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->args = $args;
        $this->event = 'postPersist';
        $this->adjustStatus();
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->args = $args;
        $this->event = 'preUpdate';
        $entity = $this->args->getEntity();
        /** prevent circular callback on product country update status */
        if(
            !$entity instanceof ProductCountry &&
            /** Only check product unit warehouse on persist */
            !$entity instanceof ProductUnitWarehouse
        ){
            $this->adjustStatus();
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $this->args = $args;
        $this->event = 'preRemove';
        $entity = $this->args->getEntity();
        /** prevent circular callback on product country update status */
        if(
            !$entity instanceof ProductCountry &&
            /** Only check product unit warehouse on persist */
            !$entity instanceof ProductUnitWarehouse
        ){
            $this->adjustStatus();
        }
    }

    private function adjustStatus()
    {
        $token = $this->container->get('security.context')->getToken();
        if (!$token) {
            return;
        }
        $user = $token->getUser();

        if ($user instanceof User && $user->isSeller() && !$user->isAffiliate()) {
            $entity = $this->args->getEntity();
            if (
                $entity instanceof Product &&
                $this->hasChanges(array(
                    'name', 'shortDescription', 'description', 'youtubeVideoUrl',
                    'brand', 'shippingCategory'
                ))
            ) {
                $this->adjustProductCountryStatus($entity);
            }
            elseif ($entity instanceof ProductUnit) {
                if (
                    !method_exists($this->args, 'hasChangedField') ||
                    $this->hasChanges(array(
                        'sku', 'price', 'discountedPrice', 'commission',
                        'length', 'width', 'weight', 'height'
                    ))
                ) {
                    $this->adjustProductCountryStatus($entity->getProduct());
                }
            }
            elseif (
                /** Handle on create & on update Translation */
                (
                    $entity instanceof Translation &&
                    $this->event == 'prePersist' &&
                    $entity->getObjectClass() == 'Yilinker\Bundle\CoreBundle\Entity\ProductUnit'
                ) ||
                (
                    $entity instanceof Translation &&
                    $entity->getObjectClass() == 'Yilinker\Bundle\CoreBundle\Entity\ProductUnit' &&
                    $this->hasChanges(array('content')) &&
                    in_array($entity->getField(), array('sku', 'price', 'discountedPrice', 'commission'))
                )
            ) {
                $em = $this->container->get('doctrine.orm.entity_manager');
                $tbProductUnit = $em->getRepository('YilinkerCoreBundle:ProductUnit');
                $unit = $tbProductUnit->find($entity->getForeignKey());
                $this->adjustProductCountryStatus($unit->getProduct());
            }
            /**  Handle ProductWarehouse change sets */
            elseif (
                $entity instanceof ProductWarehouse &&
                $this->hasChanges(array('userWarehouse', 'priority', 'logistics', 'handlingFee'))
            ) {
                $this->adjustProductCountryStatus($entity->getProduct());
            }
            elseif (
                $entity instanceof CustomBrand ||
                $entity instanceof ProductAttributeName ||
                $entity instanceof ProductImage ||
                /** Support for inserting product country */
                ($entity instanceof ProductCountry && $this->event == 'prePersist')
            ) {
                $this->adjustProductCountryStatus($entity->getProduct());
            }
            elseif (
                ($entity instanceof ProductAttributeValue || $entity instanceof ProductUnitWarehouse) &&
                $entity->getProductUnit()
            ) {
                $this->adjustProductCountryStatus($entity->getProductUnit()->getProduct());
            }
            elseif (
                $entity instanceof ProductCountry &&
                $this->args instanceof PreUpdateEventArgs &&
                ( $this->args->hasChangedField('status') &&
                $this->args->getOldValue('status') == Product::INACTIVE ) &&
                in_array($this->args->getNewValue('status'), array(Product::FOR_REVIEW))
            ) {
                $this->args->setNewValue('status', $this->args->getOldValue('status'));
            }
        }
    }

    private function adjustProductCountryStatus($product)
    {
        if (!$product) {
            return;
        }

        if (!$product->getCountry()) {
            $trans = $this->container->get('yilinker_core.translatable.listener');
            $product->setCountry($trans->getCountry(true));
        }

        if ($this->canChangeProductStatus($product) === false) {
            return;
        }

        $em = $this->container->get('doctrine.orm.entity_manager');
        $tbProductUnitWarehouse = $em->getRepository('YilinkerCoreBundle:ProductUnitWarehouse');
        $tbProduct = $em->getRepository('YilinkerCoreBundle:Product');
        $unitWarehouseCount = $tbProductUnitWarehouse->countUnitWarehousesByProduct(
            $product->getProductId()
        );
        $productWarehouses = $product->getProductWarehouses(true);
        $validUnitPrice = $tbProduct->allUnitsHavePrice($product);

        $entity = $this->args->getEntity();
        if (
            $productWarehouses &&
            $productWarehouses->count() &&
            $unitWarehouseCount &&
            $validUnitPrice
        ) {
            $entity = $this->args->getEntity();
            if (!$entity instanceof ProductUnitWarehouse) {
                $product->setStatus(Product::FOR_REVIEW);
            }
        }
        elseif ($this->event == 'prePersist') {
            $entity = $this->args->getEntity();
            if (
                !$unitWarehouseCount &&
                $entity instanceof ProductUnitWarehouse &&
                $entity->getQuantity() > 0
            ) {
                $product->setStatus(Product::FOR_REVIEW);
            }
        }
        else {
            $product->setStatus(Product::FOR_COMPLETION);
        }
    }

    private function canChangeProductStatus($product) {
        if (strpos($this->event, 'Persist') !== false && $product->getStatus() != Product::DRAFT) {
            return true;
        }
        elseif (
            /*
             * Added preRemove
             * e.g. In the API we are deleting the custom brands if it is not
             * equal to the current brand. By doing these we can prevent
             * the CustomBrand table to be bloated
             */
            (strpos($this->event, 'Update') !== false || strpos($this->event, 'Remove') !== false) &&
            $product->getStatus() != Product::INACTIVE) {
            return true;
        }

        return false;
    }

    private function hasChanges($fields)
    {
        if (method_exists($this->args, 'hasChangedField')) {
            foreach ($fields as $field) {
                $changeSet = $this->args->getEntityChangeSet();
                if (
                    $this->args->hasChangedField($field) &&
                    /** Checking false updates regarding data types (string vs int) */
                    ($changeSet[$field][0] && $changeSet[$field][1]) &&
                    ($changeSet[$field][0] != $changeSet[$field][1])
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
