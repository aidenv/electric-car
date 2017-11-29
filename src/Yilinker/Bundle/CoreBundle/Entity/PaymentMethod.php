<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentMethod
 */
class PaymentMethod
{

    const PAYMENT_METHOD_PESOPAY = 1;

    const PAYMENT_METHOD_DRAGONPAY = 2;

    const PAYMENT_METHOD_COD = 3;

    /**
     * @var integer
     */
    private $paymentMethodId;

    /**
     * @var string
     */
    private $name;

    /**
     * Get paymentMethodId
     *
     * @return integer 
     */
    public function getPaymentMethodId()
    {
        return $this->paymentMethodId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return PaymentMethod
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

    public function toArray()
    {
        $data = array(
            'paymentMethodId'   => $this->getPaymentMethodId(),
            'name'              => $this->getName()
        );

        return $data;
    }

}
