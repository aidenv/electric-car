<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

class EarningReview
{
    /**
     * @var integer
     */
    private $earningReviewId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Earning
     */
    private $earning;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductReview
     */
    private $productReview;


    /**
     * Get earningReviewId
     *
     * @return integer 
     */
    public function getEarningReviewId()
    {
        return $this->earningReviewId;
    }

    /**
     * Set earning
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Earning $earning
     * @return EarningReview
     */
    public function setEarning(\Yilinker\Bundle\CoreBundle\Entity\Earning $earning = null)
    {
        $this->earning = $earning;

        return $this;
    }

    /**
     * Get earning
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Earning 
     */
    public function getEarning()
    {
        return $this->earning;
    }

    /**
     * Set productReview
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductReview $productReview
     * @return EarningReview
     */
    public function setProductReview(\Yilinker\Bundle\CoreBundle\Entity\ProductReview $productReview = null)
    {
        $this->productReview = $productReview;

        return $this;
    }

    /**
     * Get productReview
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductReview 
     */
    public function getProductReview()
    {
        return $this->productReview;
    }
}
