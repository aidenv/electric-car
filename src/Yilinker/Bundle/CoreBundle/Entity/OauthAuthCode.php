<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Entity\AuthCode as BaseAuthCode;

/**
 * OauthAuthCode
 */
class OauthAuthCode extends BaseAuthCode
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
