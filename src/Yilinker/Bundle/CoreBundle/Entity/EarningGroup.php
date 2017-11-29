<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EarningGroup
 */
class EarningGroup
{

    const EARNING_GROUP_TRANSACTION = 1;

    const EARNING_GROUP_COMMENTS = 2;

    const EARNING_GROUP_FOLLOWERS = 3;

    const EARNING_GROUP_BUYER_NETWORK = 4;

    const EARNING_GROUP_AFFILIATE_NETWORK = 5;

    const EARNING_GROUP_WITHDRAWAL = 6;

    /**
     * @var integer
     */
    private $earningGroupId;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $imageLocation = '';

    /**
     * @var integer
     */
    private $privilegeLevel = '0';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $earningGroup;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->earningGroup = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get earningGroupId
     *
     * @return integer
     */
    public function getEarningGroupId()
    {
        return $this->earningGroupId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return EarningGroup
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

    /**
     * Set imageLocation
     *
     * @param string $imageLocation
     * @return EarningGroup
     */
    public function setImageLocation($imageLocation)
    {
        $this->imageLocation = $imageLocation;

        return $this;
    }

    /**
     * Get imageLocation
     *
     * @return string
     */
    public function getImageLocation()
    {
        return $this->imageLocation;
    }

    /**
     * Add earningGroup
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\EarningGroup $earningGroup
     * @return EarningGroup
     */
    public function addEarningGroup(\Yilinker\Bundle\CoreBundle\Entity\EarningGroup $earningGroup)
    {
        $this->earningGroup[] = $earningGroup;

        return $this;
    }

    /**
     * Remove earningGroup
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\EarningGroup $earningGroup
     */
    public function removeEarningGroup(\Yilinker\Bundle\CoreBundle\Entity\EarningGroup $earningGroup)
    {
        $this->earningGroup->removeElement($earningGroup);
    }

    /**
     * Get earningGroup
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEarningGroup()
    {
        return $this->earningGroup;
    }
}
