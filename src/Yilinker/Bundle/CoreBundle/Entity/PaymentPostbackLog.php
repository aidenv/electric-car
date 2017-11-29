<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentPostbackLog
 */
class PaymentPostbackLog
{
    /**
     * @var integer
     */
    private $paymentPostbackLogId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var string
     */
    private $data;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\PaymentMethod
     */
    private $paymentMethod;


    /**
     * Get paymentPostbackLogId
     *
     * @return integer 
     */
    public function getPaymentPostbackLogId()
    {
        return $this->paymentPostbackLogId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return PaymentPostbackLog
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set data
     *
     * @param string $data
     * @return PaymentPostbackLog
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string 
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set paymentMethod
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PaymentMethod $paymentMethod
     * @return PaymentPostbackLog
     */
    public function setPaymentMethod(\Yilinker\Bundle\CoreBundle\Entity\PaymentMethod $paymentMethod = null)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Get paymentMethod
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\PaymentMethod 
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }
}
