<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

/**
 * Class UserPointDailyLogin
 *
 * @package Yilinker\Bundle\CoreBundle\Entity
 */
class UserPointDailyLogin extends UserPoint
{

    /**
     * @var Yilinker/Bundle/CoreBundle/Entity/User $source
     */
    private $source;

    /**
     * @param Yilinker/Bundle/CoreBundle/Entity/User $source
     */
    public function setSource ($source)
    {
        $this->source = $source;
    }

    /**
     * @return Yilinker/Bundle/CoreBundle/Entity/User
     */
    public function getSource ()
    {
        return $this->source;
    }

    public function getDescription()
    {
        return "You earned ".number_format(abs($this->points), 2) ." via mobile daily login.";
    }
}
