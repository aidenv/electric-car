<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

class ShippingLeadTime
{
    
    /**
     * @var integer
     */
    private $shippingLeadTimeId;

    /**
     * @var string
     */
    private $leadTime;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Location
     */
    private $location;


    /**
     * Get shippingLeadTimeId
     *
     * @return integer 
     */
    public function getShippingLeadTimeId()
    {
        return $this->shippingLeadTimeId;
    }

    /**
     * Set leadTime
     *
     * @param string $leadTime
     * @return ShippingLeadTime
     */
    public function setLeadTime($leadTime)
    {
        $this->leadTime = $leadTime;

        return $this;
    }

    /**
     * Get leadTime
     *
     * @return string 
     */
    public function getLeadTime()
    {
        return $this->leadTime;
    }

    /**
     * Set location
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Location $location
     * @return ShippingLeadTime
     */
    public function setLocation(\Yilinker\Bundle\CoreBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Location 
     */
    public function getLocation()
    {
        return $this->location;
    }
}
