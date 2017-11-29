<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * ProductReview
 */
class ProductReview
{
    /**
     * @var integer
     */
    private $productReviewId;

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
    private $review;

    /**
     * @var string
     */
    private $rating = 0.0;

    /**
     * @var boolean
     */
    private $isHidden;

    /**
     * @var \DateTime
     */
    private $dateHidden;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $reviewer;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */

    private $product;
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    private $orderProduct;

    /**
     * Get productReviewId
     *
     * @return integer 
     */
    public function getProductReviewId()
    {
        return $this->productReviewId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ProductReview
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
     * @return ProductReview
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
     * Set review
     *
     * @param string $review
     * @return ProductReview
     */
    public function setReview($review)
    {
        $this->review = $review;

        return $this;
    }

    /**
     * Get review
     *
     * @return string 
     */
    public function getReview()
    {
        return $this->review;
    }

    /**
     * Set rating
     *
     * @param string $rating
     * @return ProductReview
     */
    public function setRating($rating)
    {
        $this->rating = $rating ? $rating: 0.0;

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
     * @return ProductReview
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
     * @return ProductReview
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
     * @return ProductReview
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
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return ProductReview
     */
    public function setProduct(\Yilinker\Bundle\CoreBundle\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set orderProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProduct
     * @return ProductReview
     */
    public function setOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProduct = null)
    {
        $this->orderProduct = $orderProduct;

        return $this;
    }

    /**
     * Get orderProduct
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProduct 
     */
    public function getOrderProduct()
    {
        return $this->orderProduct;
    }
}
