<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserFeedback
 */
class UserFeedback
{
    /**
     * @var integer
     */
    private $userFeedbackId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $feedback;

    /**
     * @var string
     */
    private $rating = '0.00';

    /**
     * @var boolean
     */
    private $isHidden = false;

    /**
     * @var \DateTime
     */
    private $dateHidden;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $reviewer;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Store
     */
    private $reviewee;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserOrder
     */
    private $order;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $ratings;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ratings = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get userFeedbackId
     *
     * @return integer 
     */
    public function getUserFeedbackId()
    {
        return $this->userFeedbackId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return UserFeedback
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
     * Set title
     *
     * @param string $title
     * @return UserFeedback
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set feedback
     *
     * @param string $feedback
     * @return UserFeedback
     */
    public function setFeedback($feedback)
    {
        $this->feedback = $feedback;

        return $this;
    }

    /**
     * Get feedback
     *
     * @return string 
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * Set rating
     *
     * @param string $rating
     * @return UserFeedback
     */
    public function setRating($rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return string 
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set isHidden
     *
     * @param boolean $isHidden
     * @return UserFeedback
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * Get isHidden
     *
     * @return boolean 
     */
    public function getIsHidden()
    {
        return $this->isHidden;
    }

    /**
     * Set dateHidden
     *
     * @param \DateTime $dateHidden
     * @return UserFeedback
     */
    public function setDateHidden($dateHidden)
    {
        $this->dateHidden = $dateHidden;

        return $this;
    }

    /**
     * Get dateHidden
     *
     * @return \DateTime 
     */
    public function getDateHidden()
    {
        return $this->dateHidden;
    }

    /**
     * Set reviewer
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $reviewer
     * @return UserFeedback
     */
    public function setReviewer(\Yilinker\Bundle\CoreBundle\Entity\User $reviewer = null)
    {
        $this->reviewer = $reviewer;

        return $this;
    }

    /**
     * Get reviewer
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getReviewer()
    {
        return $this->reviewer;
    }

    /**
     * Set reviewee
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Store $reviewee
     * @return UserFeedback
     */
    public function setReviewee(\Yilinker\Bundle\CoreBundle\Entity\Store $reviewee = null)
    {
        $this->reviewee = $reviewee;

        return $this;
    }

    /**
     * Get reviewee
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Store 
     */
    public function getReviewee()
    {
        return $this->reviewee;
    }

    /**
     * Set order
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserOrder $order
     * @return UserFeedback
     */
    public function setOrder(\Yilinker\Bundle\CoreBundle\Entity\UserOrder $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserOrder 
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Add ratings
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserFeedbackRating $ratings
     * @return UserFeedback
     */
    public function addRating(\Yilinker\Bundle\CoreBundle\Entity\UserFeedbackRating $ratings)
    {
        $this->ratings[] = $ratings;

        return $this;
    }

    /**
     * Remove ratings
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserFeedbackRating $ratings
     */
    public function removeRating(\Yilinker\Bundle\CoreBundle\Entity\UserFeedbackRating $ratings)
    {
        $this->ratings->removeElement($ratings);
    }

    /**
     * Get ratings
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRatings()
    {
        return $this->ratings;
    }

    public function getTypeToRating()
    {
        $ratings = array();
        foreach ($this->getRatings() as $rating) {
            $typeId = $rating->getType()->getFeedbackTypeId();
            $ratings[$typeId] = $rating->getRating();
        }

        return $ratings;
    }
}
