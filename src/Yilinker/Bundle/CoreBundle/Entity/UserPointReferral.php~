<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

/**
 * Class UserPointReferral
 *
 * @package Yilinker\Bundle\CoreBundle\Entity
 */
class UserPointReferral extends UserPoint
{

    /**
     * @var Yilinker/Bundle/CoreBundle/Entity/UserReferral $source
     */
    private $source;

    /**
     * @param Yilinker/Bundle/CoreBundle/Entity/UserReferral $source
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
        if (trim($this->source->getUser()->getFullname()) === "") {
            return "You earned ".number_format(abs($this->points), 2) ." via referral.";
        }

        return "You earned ".number_format(abs($this->points), 2) ." via referral of ".$this->source->getUser()->getFullname().".";
    }
}
