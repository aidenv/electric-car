<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Yilinker\Bundle\CoreBundle\Entity\Product;

/**
 * @Annotation
 */
class UniqueProductSku extends Constraint
{
    public $message = 'SKU is already taken.';

    private $userId;

    private $productUnitId;

    private $product;

    public function __construct ($userId, $productUnitId = null, Product $product = null)
    {
        $this->userId = $userId;
        $this->productUnitId = $productUnitId;
        $this->product = $product;
    }

    public function getUserId ()
    {
        return $this->userId;
    }

    public function getProductUnitId ()
    {
        return $this->productUnitId;
    }

    public function getProduct ()
    {
        return $this->product;
    }

    public function validatedBy()
    {
        return 'unique_product_sku';
    }
}