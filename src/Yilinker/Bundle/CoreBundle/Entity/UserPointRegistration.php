<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

class UserPointRegistration extends UserPoint
{

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $source;


    /**
     * Set source
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $source
     * @return UserPointRegistration
     */
    public function setSource(\Yilinker\Bundle\CoreBundle\Entity\User $source = null)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getSource()
    {
        return $this->source;
    }

    public function getDescription()
    {
        return "You earned ".number_format(abs($this->points), 2) ." via registration.";
    }
}
