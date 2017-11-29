<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PromoType
 */
class PromoType
{
    const FIXED = 1;

    const BULK = 2;

    const PER_HOUR = 3;

    const FLASH_SALE = 4;

    /**
     * @var integer
     */
    private $promoTypeId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * Get promoTypeId
     *
     * @return integer 
     */
    public function getPromoTypeId()
    {
        return $this->promoTypeId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return PromoType
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
     * Set description
     *
     * @param string $description
     * @return PromoType
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return PromoType
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

    public function toArray()
    {
        return array(
            "promoTypeId"   => $this->promoTypeId,
            "name"          => $this->name,
            "description"   => $this->description,
            "dateCreated"   => $this->dateCreated,
        );
    }
}
