<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UniqueProductSkuValidator
 * @package Yilinker\Bundle\CoreBundle\Form\Validator\Constraints
 */
class UniqueProductSkuValidator extends ConstraintValidator
{
    private $productRepository;

    /**
     * @param EntityRepository $productRepository
     */
    public function __construct(EntityRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @param string $sku
     * @param Constraint $constraint
     */
    public function validate($sku, Constraint $constraint)
    {
        $isSkuExist = false;

        if (!is_null($sku) OR $sku !== "") {
            $sku = trim($sku);
            $userId = $constraint->getUserId();
            $productUnitId = $constraint->getProductUnitId();

            $products = $this->productRepository->getProductUnitSkuByUser($userId, $sku, $productUnitId, $constraint->getProduct());

            if (sizeof($products) > 0) {
                $isSkuExist = true;
            }

        }

        if ($isSkuExist === true) {
            $this->context
                 ->buildViolation($constraint->message)
                 ->setParameter('%string%', $sku)
                 ->addViolation();
        }

    }

}