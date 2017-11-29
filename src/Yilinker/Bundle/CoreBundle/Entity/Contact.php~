<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contact
 */
class Contact
{
    /**
     * @var integer
     */
    private $contactId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $requestor;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $requestee;


    /**
     * Get contactId
     *
     * @return integer 
     */
    public function getContactId()
    {
        return $this->contactId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return Contact
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
     * Set requestor
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $requestor
     * @return Contact
     */
    public function setRequestor(\Yilinker\Bundle\CoreBundle\Entity\User $requestor = null)
    {
        $this->requestor = $requestor;

        return $this;
    }

    /**
     * Get requestor
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getRequestor()
    {
        return $this->requestor;
    }

    /**
     * Set requestee
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $requestee
     * @return Contact
     */
    public function setRequestee(\Yilinker\Bundle\CoreBundle\Entity\User $requestee = null)
    {
        $this->requestee = $requestee;

        return $this;
    }

    /**
     * Get requestee
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getRequestee()
    {
        return $this->requestee;
    }
}
