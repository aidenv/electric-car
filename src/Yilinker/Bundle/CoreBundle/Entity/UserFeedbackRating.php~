<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserFeedbackRating
 */
class UserFeedbackRating
{
    /**
     * @var integer
     */
    private $userFeedbackRatingId;

    /**
     * @var string
     */
    private $rating = '0.00';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserFeedback
     */
    private $feedbacks;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\FeedbackType
     */
    private $type;


    /**
     * Get userFeedbackRatingId
     *
     * @return integer 
     */
    public function getUserFeedbackRatingId()
    {
        return $this->userFeedbackRatingId;
    }

    /**
     * Set rating
     *
     * @param string $rating
     * @return UserFeedbackRating
     */
    public function setRating($rating)
    {
        $this->rating = $rating ? $rating: '0.00';

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
     * Set feedbacks
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserFeedback $feedbacks
     * @return UserFeedbackRating
     */
    public function setFeedbacks(\Yilinker\Bundle\CoreBundle\Entity\UserFeedback $feedbacks = null)
    {
        $this->feedbacks = $feedbacks;

        return $this;
    }

    /**
     * Get feedbacks
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserFeedback 
     */
    public function getFeedbacks()
    {
        return $this->feedbacks;
    }

    /**
     * Set type
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\FeedbackType $type
     * @return UserFeedbackRating
     */
    public function setType(\Yilinker\Bundle\CoreBundle\Entity\FeedbackType $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\FeedbackType 
     */
    public function getType()
    {
        return $this->type;
    }
}
