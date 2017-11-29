<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Entity\Client as BaseClient;

/**
 * OauthClient
 */
class OauthClient extends BaseClient
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    private $clientName = '';


    /**
     * Set clientName
     *
     * @param string $clientName
     * @return OauthClient
     */
    public function setClientName($clientName)
    {
        $this->clientName = $clientName;

        return $this;
    }

    /**
     * Get clientName
     *
     * @return string 
     */
    public function getClientName()
    {
        return $this->clientName;
    }
}
