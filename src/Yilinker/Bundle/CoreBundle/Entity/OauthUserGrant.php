<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OauthUserGrant
 */
class OauthUserGrant
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
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;


    /**
     * Set client
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OauthClient $client
     * @return OauthUserGrant
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
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return OauthUserGrant
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
    /**
     * @var \DateTime
     */
    private $dateAdded;


    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return OauthUserGrant
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
