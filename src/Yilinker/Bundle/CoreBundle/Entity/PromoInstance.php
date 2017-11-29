<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;
use Yilinker\Bundle\CoreBundle\Entity\ProductPromoMap;
use Yilinker\Bundle\CoreBundle\Entity\PromoType;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Doctrine\ORM\Query\Expr\Join;

use \DateTime;
use Carbon\Carbon;

/**
 * PromoInstance
 */
class PromoInstance
{
    /**
     * @var integer
     */
    private $promoInstanceId;

    /**
     * @var \DateTime
     */
    private $dateStart;

    /**
     * @var \DateTime
     */
    private $dateEnd;

    /**
     * @var boolean
     */
    private $isEnabled;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\PromoType
     */
    private $promoType;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var string
     */
    private $advertisement;

    /**
     * @var boolean
     */
    private $isImageAdvertisement;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productPromoMap;

    /**
     * @var string
     */
    private $title;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productPromoMap = new ArrayCollection();
    }

    /**
     * Set dateStart
     *
     * @param \DateTime $dateStart
     * @return PromoInstance
     */
    public function setDateStart($dateStart, $format = "m-d-Y H:i:s")
    {
        if($dateStart instanceof DateTime){
            $this->dateStart = $dateStart;
        }
        else{
            $this->dateStart = Carbon::createFromFormat($format, $dateStart);
        }

        return $this;
    }

    /**
     * Get dateStart
     *
     * @return \DateTime
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * Get promoInstanceId
     *
     * @return integer
     */
    public function getPromoInstanceId()
    {
        return $this->promoInstanceId;
    }

    /**
     * Set dateEnd
     *
     * @param \DateTime $dateEnd
     * @return PromoInstance
     */
    public function setDateEnd($dateEnd, $format = "m-d-Y H:i:s")
    {
        if($dateEnd instanceof DateTime){
            $this->dateEnd = $dateEnd;
        }
        else{
            $this->dateEnd = Carbon::createFromFormat($format, $dateEnd);
        }

        return $this;
    }

    /**
     * Get dateEnd
     *
     * @return \DateTime
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * Set isEnabled
     *
     * @param boolean $isEnabled
     * @return PromoInstance
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get isEnabled
     *
     * @return boolean
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return PromoInstance
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set advertisement
     *
     * @param string $advertisement
     * @return PromoInstance
     */
    public function setAdvertisement($advertisement)
    {
        $this->advertisement = $advertisement;

        return $this;
    }

    /**
     * Get advertisement
     *
     * @return string
     */
    public function getAdvertisement()
    {
        return $this->advertisement;
    }

    /**
     * Set isImageAdvertisement
     *
     * @param boolean $isImageAdvertisement
     * @return PromoInstance
     */
    public function setIsImageAdvertisement($isImageAdvertisement)
    {
        $this->isImageAdvertisement = $isImageAdvertisement;

        return $this;
    }

    /**
     * Get isImageAdvertisement
     *
     * @return boolean
     */
    public function getIsImageAdvertisement()
    {
        return $this->isImageAdvertisement;
    }

    /**
     * Add productPromoMap
     *
     * @param ProductPromoMap $productPromoMap
     * @return PromoInstance
     */
    public function addProductPromoMap(ProductPromoMap $productPromoMap)
    {
        $this->productPromoMap[] = $productPromoMap;

        return $this;
    }

    /**
     * Remove productPromoMap
     *
     * @param ProductPromoMap $productPromoMap
     */
    public function removeProductPromoMap(ProductPromoMap $productPromoMap)
    {
        $this->productPromoMap->removeElement($productPromoMap);
    }

    /**
     * Get productPromoMap
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductPromoMap()
    {
        return $this->productPromoMap;
    }

    public function getOrderedProductPromoMap()
    {
        $criteria = Criteria::create()
                            ->orderBy(array("sortOrder" => Criteria::ASC));
        $productPromoMap = $this->getProductPromoMap()->matching($criteria);

        return $productPromoMap;
    }

    public function getProductPromoMapByUnit($unit)
    {
        $criteria = Criteria::create()
                    ->where(Criteria::expr()->eq("productUnit", $unit))
                    ->andWhere(Criteria::expr()->eq("promoInstance", $this))
                    ->setMaxResults(1);

        $productPromoMap = $this->getProductPromoMap()->matching($criteria);

        if($productPromoMap){
            return $productPromoMap->first();
        }

        return null;
    }

    public function getOrderedFeaturedProductPromoMap()
    {
        $criteria = Criteria::create()
                            ->orderBy(array("sortOrder" => Criteria::ASC))
                            ->setFirstResult(0)
                            ->setMaxResults(3);
        $productPromoMap = $this->getProductPromoMap()->matching($criteria);

        return $productPromoMap;
    }

    /**
     * Set promoType
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PromoType $promoType
     * @return PromoInstance
     */
    public function setPromoType(PromoType $promoType = null)
    {
        $this->promoType = $promoType;

        return $this;
    }

    /**
     * Get promoType
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\PromoType
     */
    public function getPromoType()
    {
        return $this->promoType;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return PromoInstance
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function toArray($productsIncluded = true, $isOrdered = false, $activeProducts = false)
    {
        $promoInstance = array(
            "promoInstanceId"       => $this->promoInstanceId,
            "dateStart"             => $this->dateStart,
            "dateEnd"               => $this->dateEnd,
            "title"                 => $this->title,
            "isEnabled"             => $this->isEnabled,
            "promoType"             => $this->promoType->toArray(),
            "dateCreated"           => $this->dateCreated,
            "advertisement"         => $this->advertisement,
            "isImageAdvertisement"  => $this->isImageAdvertisement,
        );

        if($productsIncluded){
            if($isOrdered){
                $productPromoMaps = $this->getOrderedProductPromoMap();
            }
            else{
                $productPromoMaps = $this->getProductPromoMap();
            }

            $productUnits = array();

            foreach($productPromoMaps as $productPromoMap){
                $productUnit = $productPromoMap->getProductUnit();

                if($productUnit){

                    $product = $productUnit->getProduct();

                    if(
                        $activeProducts &&
                        $product->getStatus() == Product::ACTIVE &&
                        $productUnit->getQuantity() > 0
                    ){

                        $productUnits[$productUnit->getProductUnitId()] = array(
                            "productId"         => $product->getProductId(),
                            "product"           => $product,
                            "name"              => $product->getName(),
                            "sku"               => $productUnit->getSku(),
                            "productUnitId"     => $productUnit->getProductUnitId(),
                            "maxQuantity"       => $productPromoMap->getMaxQuantity(),
                            "price"             => $productUnit->getPrice(),
                            "discountedPrice"   => $productPromoMap->getDiscountedPrice(),
                            "minimumPercentage" => $productPromoMap->getMinimumPercentage(),
                            "maximumPercentage" => $productPromoMap->getMaximumPercentage(),
                            "percentPerHour"    => $productPromoMap->getPercentPerHour(),
                            "quantityRequired"  => $productPromoMap->getQuantityRequired(),
                        );
                    }
                    elseif(!$activeProducts){
//
                        $productUnits[$productUnit->getProductUnitId()] = array(
                            "productId"         => $product->getProductId(),
                            "productUnitId"     => $productUnit->getProductUnitId(),
                            "name"              => $product->getName(),
                            "sku"               => $productUnit->getSku(),
                            "maxQuantity"       => $productPromoMap->getMaxQuantity(),
                            "price"             => $productUnit->getPrice(),
                            "discountedPrice"   => $productPromoMap->getDiscountedPrice(),
                            "minimumPercentage" => $productPromoMap->getMinimumPercentage(),
                            "maximumPercentage" => $productPromoMap->getMaximumPercentage(),
                            "percentPerHour"    => $productPromoMap->getPercentPerHour(),
                            "quantityRequired"  => $productPromoMap->getQuantityRequired(),
                        );
                    }
                }
            }

            $promoInstance["productUnits"] = $productUnits;
            $promoInstance["productUnitsCount"] = count($productUnits);
        }

        return $promoInstance;
    }

    private function getDetails($product, $productUnit)
    {
        return array(
            "id"    => $product->getProductId(),
            "productUnitId" => $productUnit->getProductUnitId(),
            "name"  => $product->getName(),
            "price"  => $productUnit->getPrice(),
            "discountedPrice"  => $productUnit->getAppliedDiscountPrice()
        );
    }
}
