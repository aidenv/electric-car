<?php 

 namespace Yilinker\Bundle\CoreBundle\Services\Product;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductVisit;

class ProductVisitService
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function addProductVisit(Product $product, $ipAddress)
    {
        $productVisit = new ProductVisit();
        $productVisit->setProduct($product)
                     ->setIpAddress($ipAddress)
                     ->setDateAdded(Carbon::now());

        $this->em->persist($productVisit);
        $this->em->flush();
    }
}
