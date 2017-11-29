<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OauthAdminGrant
 */
class OauthAdminGrant
{
    /**
     * @var integer
     */
    private $id;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OauthClient
     */
    private $client;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $user;


    /**
     * Set client
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OauthClient $client
     * @return OauthAdminGrant
     */
    public function setClient(\Yilinker\Bundle\CoreBundle\Entity\OauthClient $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OauthClient 
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $user
     * @return OauthAdminGrant
     */
    public function setUser(\Yilinker\Bundle\CoreBundle\Entity\AdminUser $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AdminUser 
     */
    public function getUser()
    {
        return $this->user;
    }
    /**
     * @var \DateTime
     */
    private $dateAdded;


    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return OauthAdminGrant
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
}
