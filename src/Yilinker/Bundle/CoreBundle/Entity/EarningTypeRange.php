<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EarningTypeRange
 */
class EarningTypeRange
{
    /**
     * @var integer
     */
    private $earningTypeRange;

    /**
     * @var integer
     */
    private $from;

    /**
     * @var integer
     */
    private $to;

    /**
     * @var string
     */
    private $earning;

    /**
     * @var string
     */
    private $bonus;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\EarningType
     */
    private $earningType;


    /**
     * Get earningTypeRange
     *
     * @return integer 
     */
    public function getEarningTypeRange()
    {
        return $this->earningTypeRange;
    }

    /**
     * Set from
     *
     * @param integer $from
     * @return EarningTypeRange
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get from
     *
     * @return integer 
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set to
     *
     * @param integer $to
     * @return EarningTypeRange
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get to
     *
     * @return integer 
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set earning
     *
     * @param string $earning
     * @return EarningTypeRange
     */
    public function setEarning($earning)
    {
        $this->earning = $earning;

        return $this;
    }

    /**
     * Get earning
     *
     * @return string 
     */
    public function getEarning()
    {
        return $this->earning;
    }

    /**
     * Set bonus
     *
     * @param string $bonus
     * @return EarningTypeRange
     */
    public function setBonus($bonus)
    {
        $this->bonus = $bonus;

        return $this;
    }

    /**
     * Get bonus
     *
     * @return string 
     */
    public function getBonus()
    {
        return $this->bonus;
    }

    /**
     * Set earningType
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\EarningType $earningType
     * @return EarningTypeRange
     */
    public function setEarningType(\Yilinker\Bundle\CoreBundle\Entity\EarningType $earningType = null)
    {
        $this->earningType = $earningType;

        return $this;
    }

    /**
     * Get earningType
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\EarningType 
     */
    public function getEarningType()
    {
        return $this->earningType;
    }
}
