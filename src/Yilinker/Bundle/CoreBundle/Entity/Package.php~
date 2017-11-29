<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;

/**
 * Package
 */
class Package
{
    /**
     * @var integer
     */
    private $packageId;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var string
     */
    private $waybillNumber = false;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\PackageStatus
     */
    private $packageStatus;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $packageDetails;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserOrder
     */
    private $userOrder;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $packageHistory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->packageDetails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->packageHistory = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get packageId
     *
     * @return integer 
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Set waybillNumber
     *
     * @param string $waybillNumber
     * @return Package
     */
    public function setWaybillNumber($waybillNumber)
    {
        $this->waybillNumber = $waybillNumber;

        return $this;
    }

    /**
     * Get waybillNumber
     *
     * @return string 
     */
    public function getWaybillNumber()
    {
        return $this->waybillNumber;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return Package
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set packageStatus
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PackageStatus $packageStatus
     * @return Package
     */
    public function setPackageStatus(\Yilinker\Bundle\CoreBundle\Entity\PackageStatus $packageStatus = null)
    {
        $this->packageStatus = $packageStatus;

        return $this;
    }

    /**
     * Get packageStatus
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\PackageStatus 
     */
    public function getPackageStatus()
    {
        return $this->packageStatus;
    }

    /**
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return Package
     */
    public function setDateLastModified($dateLastModified)
    {
        $this->dateLastModified = $dateLastModified;

        return $this;
    }

    /**
     * Get dateLastModified
     *
     * @return \DateTime 
     */
    public function getDateLastModified()
    {
        return $this->dateLastModified;
    }


    /**
     * Add packageDetails
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PackageDetail $packageDetails
     * @return Package
     */
    public function addPackageDetail(\Yilinker\Bundle\CoreBundle\Entity\PackageDetail $packageDetails)
    {
        $this->packageDetails[] = $packageDetails;

        return $this;
    }

    /**
     * Remove packageDetails
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PackageDetail $packageDetails
     */
    public function removePackageDetail(\Yilinker\Bundle\CoreBundle\Entity\PackageDetail $packageDetails)
    {
        $this->packageDetails->removeElement($packageDetails);
    }

    /**
     * Get packageDetails
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPackageDetails()
    {
        return $this->packageDetails;
    }

    /**
     * Set userOrder
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserOrder $userOrder
     * @return Package
     */
    public function setUserOrder(\Yilinker\Bundle\CoreBundle\Entity\UserOrder $userOrder = null)
    {
        $this->userOrder = $userOrder;

        return $this;
    }

    /**
     * Get userOrder
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserOrder 
     */
    public function getUserOrder()
    {
        return $this->userOrder;
    }

    /**
     * Add packageHistory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PackageHistory $packageHistory
     * @return Package
     */
    public function addPackageHistory(\Yilinker\Bundle\CoreBundle\Entity\PackageHistory $packageHistory)
    {
        $this->packageHistory[] = $packageHistory;

        return $this;
    }

    /**
     * Remove packageHistory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PackageHistory $packageHistory
     */
    public function removePackageHistory(\Yilinker\Bundle\CoreBundle\Entity\PackageHistory $packageHistory)
    {
        $this->packageHistory->removeElement($packageHistory);
    }

    /**
     * Get packageHistory
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPackageHistory()
    {
        return $this->packageHistory;
    }

    public function getTimelinedHistory($format = 'F j')
    {
        $criteria = Criteria::create()->orderBy(array('dateAdded' => 'DESC'));

        $histories = $this->getPackageHistory()->matching($criteria);
        $timeline = array();
        foreach ($histories as $history) {
            $date = $history->getDateAdded($format);
            $timeline[$date][] = $history;
        }

        return $timeline;
    }

    /**
     * Get most recent package history
     *
     * @return Yilinker\Bundle\CoreBundle\Entity\PackageHistory
     */
    public function getMostRecentPackageHistory()
    {
        $criteria = Criteria::create()->orderBy(array('dateAdded' => 'DESC'))
                                      ->setMaxResults(1);        
        $recentHistory = $this->getPackageHistory()->matching($criteria)->first();

        return $recentHistory ? $recentHistory : null;
    }

    /**
     * Get order products
     *
     * @return Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    public function getOrderProducts()
    {
        $orderProducts = new \Doctrine\Common\Collections\ArrayCollection();
        $packageDetails = $this->getPackageDetails();
        foreach($packageDetails as $packageDetail){
            $orderProducts[] = $packageDetail->getOrderProduct();
        }

        return $orderProducts;
    }

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Warehouse
     */
    private $warehouse;


    /**
     * Set warehouse
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Warehouse $warehouse
     * @return Package
     */
    public function setWarehouse(\Yilinker\Bundle\CoreBundle\Entity\Warehouse $warehouse = null)
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    /**
     * Get warehouse
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Warehouse 
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }
}
