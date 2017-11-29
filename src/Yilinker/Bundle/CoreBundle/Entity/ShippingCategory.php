<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Yilinker\Bundle\CoreBundle\Entity\Utility\YilinkerTranslatable as Translatable;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShippingCategory
 */
class ShippingCategory extends Translatable
{
    /**
     * @var integer
     */
    private $shippingCategoryId;

    /**
     * @var string
     */
    private $name;


    /**
     * Get shippingCategoryId
     *
     * @return integer 
     */
    public function getShippingCategoryId()
    {
        return $this->shippingCategoryId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ShippingCategory
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

    public function __toString()
    {
        return (string) $this->name;
    }

    public function toArray()
    {
        return array(
            'shippingCategoryId' => $this->shippingCategoryId,
            'name' => (string) $this->name
        );
    }
}
