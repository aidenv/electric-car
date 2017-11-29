<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * PromoEvent
 */
class PromoEvent
{
    /**
     * @var integer
     */
    private $promoEventId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var \DateTime
     */
    private $dateStart;

    /**
     * @var \DateTime
     */
    private $dateEnd;

    /**
     * @var boolean
     */
    private $isActive = false;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $promoEventUsers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->promoEventUsers = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get promoEventId
     *
     * @return integer 
     */
    public function getPromoEventId()
    {
        return $this->promoEventId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return PromoEvent
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return PromoEvent
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateStart
     *
     * @param \DateTime $dateStart
     * @return PromoEvent
     */
    public function setDateStart($dateStart)
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    /**
     * Get dateStart
     *
     * @return \DateTime 
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * Set dateEnd
     *
     * @param \DateTime $dateEnd
     * @return PromoEvent
     */
    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    /**
     * Get dateEnd
     *
     * @return \DateTime 
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return PromoEvent
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Add promoEventUsers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PromoEventUser $promoEventUsers
     * @return PromoEvent
     */
    public function addPromoEventUser(\Yilinker\Bundle\CoreBundle\Entity\PromoEventUser $promoEventUsers)
    {
        $this->promoEventUsers[] = $promoEventUsers;

        return $this;
    }

    /**
     * Remove promoEventUsers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PromoEventUser $promoEventUsers
     */
    public function removePromoEventUser(\Yilinker\Bundle\CoreBundle\Entity\PromoEventUser $promoEventUsers)
    {
        $this->promoEventUsers->removeElement($promoEventUsers);
    }

    /**
     * Get promoEventUsers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPromoEventUsers()
    {
        return $this->promoEventUsers;
    }

    public function isUserSubscribed($user)
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("user", $user))
                            ->setFirstResult(0)
                            ->setMaxResults(1);
        $user = $this->getPromoEventUsers()->matching($criteria)->first();

        if(!$user){
            return false;
        }

        return true;
    }
}
