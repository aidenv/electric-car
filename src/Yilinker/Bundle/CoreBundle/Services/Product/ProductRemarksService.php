<?php

namespace Yilinker\Bundle\CoreBundle\Services\Product;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;
use Yilinker\Bundle\CoreBundle\Entity\Country;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductRemarks;

/**
 * Class ProductRemarksService
 *
 * @package Yilinker\Bundle\CoreBundle\Services\Product
 */
class ProductRemarksService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Add ProductRemarks
     *
     * @param Product $product
     * @param AdminUser $adminUser
     * @param $remarks
     * @param int $productStatus
     * @return ProductRemarks
     */
    public function addProductRemarks (
        Product $product,
        AdminUser $adminUser,
        $remarks,
        $productStatus = Product::REJECT,
        $countryCode = Country::COUNTRY_CODE_PHILIPPINES
    ) {
        $productRemarks = new ProductRemarks();
        $productRemarks->setProduct($product);
        $productRemarks->setAdminUser($adminUser);
        $productRemarks->setDateAdded(Carbon::now());
        $productRemarks->setRemarks($remarks);
        $productRemarks->setProductStatus($productStatus)
                       ->setCountryCode($countryCode);

        $this->em->persist($productRemarks);
        $this->em->flush();

        return $productRemarks;
    }

}
