<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Entity\AccessToken as BaseAccessToken;

/**
 * OauthAccessToken
 */
class OauthAccessToken extends BaseAccessToken
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
