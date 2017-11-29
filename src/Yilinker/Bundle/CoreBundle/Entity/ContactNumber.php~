<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContactNumber
 */
class ContactNumber
{
    /**
     * @var integer
     */
    private $contactNumberId;

    /**
     * @var string
     */
    private $contactNumber;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */   
    private $user;


    /**
     * Get contactNumberId
     *
     * @return integer 
     */
    public function getContactNumberId()
    {
        return $this->contactNumberId;
    }

    /**
     * Set contactNumber
     *
     * @param string $contactNumber
     * @return ContactNumber
     */
    public function setContactNumber($contactNumber)
    {
        $this->contactNumber = $contactNumber;

        return $this;
    }

    /**
     * Get contactNumber
     *
     * @return string 
     */
    public function getContactNumber()
    {
        return $this->contactNumber;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return ContactNumber
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
}
