<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MobileFeedBackAdmin
 */
class MobileFeedBackAdmin
{
    /**
     * @var integer
     */
    private $mobileFeedbackAdminId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $adminUser;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\MobileFeedBack
     */
    private $mobileFeedback;


    /**
     * Get mobileFeedbackAdminId
     *
     * @return integer 
     */
    public function getMobileFeedbackAdminId()
    {
        return $this->mobileFeedbackAdminId;
    }

    /**
     * Set adminUser
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser
     * @return MobileFeedBackAdmin
     */
    public function setAdminUser(\Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser = null)
    {
        $this->adminUser = $adminUser;

        return $this;
    }

    /**
     * Get adminUser
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AdminUser 
     */
    public function getAdminUser()
    {
        return $this->adminUser;
    }

    /**
     * Set mobileFeedback
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\MobileFeedBack $mobileFeedback
     * @return MobileFeedBackAdmin
     */
    public function setMobileFeedback(\Yilinker\Bundle\CoreBundle\Entity\MobileFeedBack $mobileFeedback = null)
    {
        $this->mobileFeedback = $mobileFeedback;

        return $this;
    }

    /**
     * Get mobileFeedback
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\MobileFeedBack 
     */
    public function getMobileFeedback()
    {
        return $this->mobileFeedback;
    }
}
