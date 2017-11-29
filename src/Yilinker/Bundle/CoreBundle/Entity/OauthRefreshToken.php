<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Entity\RefreshToken as BaseRefreshToken;

/**
 * OauthRefreshToken
 */
class OauthRefreshToken extends BaseRefreshToken
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OauthClient
     */
    protected $client;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    protected $user;
}
