<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OauthProvider
 */
class OauthProvider
{

    const OAUTH_PROVIDER_FACEBOOK = 1;

    const OAUTH_PROVIDER_GOOGLE = 2;

    /**
     * @var integer
     */
    private $oauthProviderId;

    /**
     * @var string
     */
    private $name = '';


    /**
     * Get oauthProviderId
     *
     * @return integer 
     */
    public function getOauthProviderId()
    {
        return $this->oauthProviderId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return OauthProvider
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
}
