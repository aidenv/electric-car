<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

class UserPointReferralPurchase extends UserPoint
{
    
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    private $source;


    /**
     * Set source
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $source
     * @return UserPointReferralPurchase
     */
    public function setSource(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $source = null)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProduct 
     */
    public function getSource()
    {
        return $this->source;
    }

    public function getDescription()
    {
        return "You earned ".number_format(abs($this->points), 2) ." via referral purchase.";
    }
}
