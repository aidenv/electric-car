<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Package
 */
class PackageStatus
{
    const STATUS_FOR_VERIFICATION = 15;

    const STATUS_READY_FOR_PICKUP = 20;
    
    const STATUS_ACKNOWLEDGED_FOR_PICKUP = 30;
    
    const STATUS_CHECKED_IN_BY_RIDER_FOR_TRANSFER = 40;

    const STATUS_CHECKED_IN_BY_RIDER_FOR_DELIVERY = 80;

    const STATUS_RECEIVED_BY_RECIPIENT = 90;

    const STATUS_CANCELLED = 120;

    /**
     * @var integer
     */
    private $packageStatusId;

    /**
     * @var string
     */
    private $name = false;


    /**
     * Get packageStatusId
     *
     * @return integer 
     */
    public function getPackageStatusId()
    {
        return $this->packageStatusId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return PackageStatus
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }
}
