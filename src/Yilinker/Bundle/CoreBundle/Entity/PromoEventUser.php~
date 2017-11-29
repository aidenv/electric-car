<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PromoEventUser
 */
class PromoEventUser
{
    /**
     * @var integer
     */
    private $promoEventUserId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\PromoEvent
     */
    private $promoEvent;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;


    /**
     * Get promoEventUserId
     *
     * @return integer 
     */
    public function getPromoEventUserId()
    {
        return $this->promoEventUserId;
    }

    /**
     * Set promoEvent
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PromoEvent $promoEvent
     * @return PromoEventUser
     */
    public function setPromoEvent(\Yilinker\Bundle\CoreBundle\Entity\PromoEvent $promoEvent = null)
    {
        $this->promoEvent = $promoEvent;

        return $this;
    }

    /**
     * Get promoEvent
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\PromoEvent 
     */
    public function getPromoEvent()
    {
        return $this->promoEvent;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return PromoEventUser
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
}
