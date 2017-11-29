<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefundNote
 */
class RefundNote extends Note
{
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Payout
     */
    private $source;


    /**
     * Set source
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Payout $source
     * @return RefundNote
     */
    public function setSource(\Yilinker\Bundle\CoreBundle\Entity\Payout $source = null)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Payout 
     */
    public function getSource()
    {
        return $this->source;
    }
}
