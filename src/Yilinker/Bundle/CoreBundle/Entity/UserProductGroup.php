<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Yilinker\Bundle\CoreBundle\Entity\Utility\YilinkerTranslatable as Translatable;

/**
 * UserProductGroup
 */
class UserProductGroup
{
    /**
     * @var integer
     */
    private $userProductGroupId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;


    /**
     * Get userProductGroupId
     *
     * @return integer 
     */
    public function getUserProductGroupId()
    {
        return $this->userProductGroupId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return UserProductGroup
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
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return UserProductGroup
     */
    public function setUser(\Yilinker\Bundle\CoreBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function toArray()
    {
        return array(
            "id" => $this->userProductGroupId,
            "name" => $this->name
        );
    }
}
