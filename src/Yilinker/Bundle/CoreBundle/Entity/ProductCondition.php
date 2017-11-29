<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductCondition
 */
class ProductCondition
{

    /**
     * @var integer
     */
    private $productConditionId;

    /**
     * @var string
     */
    private $name;


    /**
     * Get productConditionId
     *
     * @return integer 
     */
    public function getProductConditionId()
    {
        return $this->productConditionId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ProductCondition
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
     * Convert the object to an array
     */
    public function toArray($absoluteKey = false)
    {
        return array(
            'name' => $this->name,
            $absoluteKey? 'productConditionId' : 'id'   => $this->productConditionId,
        );
    }
}
