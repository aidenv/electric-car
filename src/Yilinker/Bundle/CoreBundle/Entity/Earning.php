<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\EarningTransaction;
use Yilinker\Bundle\CoreBundle\Entity\EarningUserRegistration;
use Yilinker\Bundle\CoreBundle\Entity\EarningFollow;
use Yilinker\Bundle\CoreBundle\Entity\EarningReview;

class Earning
{

    const TENTATIVE = 0;
    const COMPLETE = 1;
    const INVALID = 2;
    const WITHDRAW = 3;

    /**
     * @var integer
     */
    private $earningId;

    /**
     * @var string
     */
    private $amount = '0.00';

    /**
     * @var integer
     */
    private $status = 0;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\EarningType
     */
    private $earningType;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\EarningFollow
     */
    private $earningFollow;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\EarningReview
     */
    private $earningReview;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\EarningTransaction
     */
    private $earningTransaction;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\EarningUserRegistration
     */
    private $earningUserRegistration;

    /**
     * Get earningId
     *
     * @return integer 
     */
    public function getEarningId()
    {
        return $this->earningId;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return Earning
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Earning
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus($text = false)
    {
        if ($text) {
            switch ($this->status) {
                case self::TENTATIVE:
                    return 'Tentative';
                case self::COMPLETE:
                    return 'Completed';
                case self::INVALID:
                    return 'Invalid';
                case self::WITHDRAW:
                    return 'Withdraw';
            }
        }

        return $this->status;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return Earning
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
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return Earning
     */
    public function setDateLastModified($dateLastModified)
    {
        $this->dateLastModified = $dateLastModified;

        return $this;
    }

    /**
     * Get dateLastModified
     *
     * @return \DateTime 
     */
    public function getDateLastModified()
    {
        return $this->dateLastModified;
    }

    /**
     * Set earningType
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\EarningType $earningType
     * @return Earning
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

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return Earning
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

    /**
     * Set earningFollow
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\EarningFollow $earningFollow
     * @return Earning
     */
    public function setEarningFollow(\Yilinker\Bundle\CoreBundle\Entity\EarningFollow $earningFollow = null)
    {
        $this->earningFollow = $earningFollow;

        return $this;
    }

    /**
     * Get earningFollow
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\EarningFollow 
     */
    public function getEarningFollow()
    {
        return $this->earningFollow;
    }

    /**
     * Set earningReview
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\EarningReview $earningReview
     * @return Earning
     */
    public function setEarningReview(\Yilinker\Bundle\CoreBundle\Entity\EarningReview $earningReview = null)
    {
        $this->earningReview = $earningReview;

        return $this;
    }

    /**
     * Get earningReview
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\EarningReview 
     */
    public function getEarningReview()
    {
        return $this->earningReview;
    }

    /**
     * Set earningTransaction
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\EarningTransaction $earningTransaction
     * @return Earning
     */
    public function setEarningTransaction(\Yilinker\Bundle\CoreBundle\Entity\EarningTransaction $earningTransaction = null)
    {
        $earningTransaction->setEarning($this);
        $this->earningTransaction = $earningTransaction;

        return $this;
    }

    /**
     * Get earningTransaction
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\EarningTransaction 
     */
    public function getEarningTransaction()
    {
        return $this->earningTransaction;
    }

    /**
     * @return Yilinker\Bundle\CoreBundle\Entity\Utility\EarningObjectType
     */
    public function getEarningObjectType()
    {
        $objectType = null;

        if ($objectType = $this->getEarningFollow()) {
            return $objectType;
        }
        elseif ($objectType = $this->getEarningReview()) {
            return $objectType;
        }
        elseif ($objectType = $this->getEarningTransaction()) {
            return $objectType;
        }
        elseif ($objectType = $this->getEarningUserRegistration()) {
            return $objectType;
        }

        return $objectType;
    }


    public function getEarningTransactionUserOrderStatus()
    {
        if ( ($this->status == self::TENTATIVE || $this->status == self::INVALID) 
                && $objectType = $this->getEarningTransaction() )
        {
            return $objectType->getOrder() ? $objectType->getOrder()->getOrderStatus()->getName() : null;
        }

        return null;
    }

    public function getDescription()
    {
        if (!isset($this->description)) {
            $this->description = '';
            $earningObjectType = $this->getEarningObjectType();

            if ($earningObjectType instanceof EarningTransaction) {
                $orderProduct = $earningObjectType->getOrderProduct();
                if ($orderProduct) {
                    $order = $orderProduct->getOrder();
                    $buyer = $order->getBuyer();
                    $earningType = $this->getEarningType();
                    $amount = $this->getAmount() ? $this->getAmount(): 0;
                    $this->description = $order->getInvoiceNumber().'\n'.
                                         $orderProduct->getProductName().'\n'.
                                         'Bought by: '.$buyer->getFullName();
                }
                elseif ($order = $earningObjectType->getOrder()) {
                    $this->description = 'Earned in Buyer `'.$order->getBuyer()->getFullName().'\'s` transaction';
                }
            }
            elseif ($earningObjectType instanceof EarningUserRegistration) {
                $user = $earningObjectType->getUser();
                $isSeller = $user->isSeller();
                $role = 'Buyer';
                if ($isSeller) {
                    if ($user->getStore()->isAffiliate()) {
                        $role = 'Affiliate';
                    }
                    else {
                        $role = 'Seller';
                    }
                }
                $this->description = $role.' `'.$user->getFullName().'` linked to your network';
            }
            elseif ($earningObjectType instanceof EarningFollow) {
                $follower = $earningObjectType->getUserFollowHistory()->getFollower();
                $fullname = trim($follower->getFullName());
                if ($follower->isSeller()) {
                    $this->description = ($fullname ? 'Affiliate `'.$fullname.'`': 'An affiliate').' followed you';
                }
                else {
                    $this->description = ($fullname ? 'Buyer `'.$fullname.'`': 'A buyer').' followed you';
                }
            }
            elseif ($earningObjectType instanceof EarningReview) {
                $productReview = $earningObjectType->getProductReview();
                $fullname = $productReview->getReviewer()->getFullName();
                $productName = $productReview->getOrderProduct()->getProductName();
                $this->description = ($fullname ? 'Buyer `'.$fullname.'`': 'A buyer').' reviewed the product `'.$productName.'`';
            }
        }

        return $this->description;
    }

    public static function getStatuses()
    {
        return array(
            self::TENTATIVE => 'Tentative',
            self::COMPLETE  => 'Complete',
            self::INVALID   => 'Invalid'
        );
    }

    /**
     * Set earningUserRegistration
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\EarningUserRegistration $earningUserRegistration
     * @return Earning
     */
    public function setEarningUserRegistration(\Yilinker\Bundle\CoreBundle\Entity\EarningUserRegistration $earningUserRegistration = null)
    {
        $this->earningUserRegistration = $earningUserRegistration;

        return $this;
    }

    /**
     * Get earningUserRegistration
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\EarningUserRegistration 
     */
    public function getEarningUserRegistration()
    {
        return $this->earningUserRegistration;
    }
}
