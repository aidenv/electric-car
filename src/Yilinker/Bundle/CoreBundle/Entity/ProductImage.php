<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductImage
 */
class ProductImage
{

    const TEMP_FOLDER = 'products/temp/';

    const PRODUCT_FOLDER = 'products/';

    const PRODUCT_FOLDER_SMALL = 'small';

    const PRODUCT_FOLDER_MEDIUM = 'medium';

    const PRODUCT_FOLDER_LARGE = 'large';

    const PRODUCT_FOLDER_THUMBNAIL = 'thumbnail';

    const SIZE_THUMBNAIL_WIDTH = 200;

    const SIZE_THUMBNAIL_HEIGHT = 225;

    const SIZE_SMALL_WIDTH = 200;

    const SIZE_SMALL_HEIGHT = 225;

    const SIZE_MEDIUM_WIDTH = 600;

    const SIZE_MEDIUM_HEIGHT = 677;

    const SIZE_LARGE_WIDTH = 1200;

    const SIZE_LARGE_HEIGHT = 1354;
    
    /**
     * @var integer
     */
    private $productImageId;

    /**
     * @var string
     */
    private $imageLocation = '';

    /**
     * @var boolean
     */
    private $isPrimary = '0';

    /**
     * @var boolean
     */
    private $isDeleted = '0';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;

    /**
     * The full image path (populated by listener)
     *
     * @var string
     */
    private $fullImagePath = null;

    /**
     * @var string
     */
    private $defaultLocale = 'en';

    /**
     * Get productImageId
     *
     * @return integer 
     */
    public function getProductImageId()
    {
        return $this->productImageId;
    }

    /**
     * Set imageLocation
     *
     * @param string $imageLocation
     * @return ProductImage
     */
    public function setImageLocation($imageLocation)
    {
        $this->imageLocation = $imageLocation;

        return $this;
    }

    /**
     * Get imageLocation
     *
     * @return string 
     */
    public function getImageLocation($isRaw = false)
    {
        if($isRaw){
            return $this->imageLocation;
        }
        else{
//            return $this->product->getProductId().'/'.$this->imageLocation;
            return '';
        }
    }

    /**
     * Get raw imageLocation
     *
     * @return string 
     */
    public function getRawImageLocation()
    {
        return $this->imageLocation;
    }


    /**
     * Set isPrimary
     *
     * @param boolean $isPrimary
     * @return ProductImage
     */
    public function setIsPrimary($isPrimary)
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }

    /**
     * Get isPrimary
     *
     * @return boolean 
     */
    public function getIsPrimary()
    {
        return $this->isPrimary;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return ProductImage
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return boolean 
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return ProductImage
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
     * Get the full image path
     *
     * @return string
     */
    public function getFullImagePath()
    {
        return $this->fullImagePath;
    }

    /**
     * Set the full image path
     *
     * @param string $fullImagePath
     */
    public function setFullImagePath($fullImagePath)
    {
        $this->fullImagePath = $fullImagePath;
    }
    
    public function getImageLocationBySize($size = null)
    {
        if($this->imageLocation !== "" || $this->imageLocation !== null){
            $folder = !is_null($size)? $size.DIRECTORY_SEPARATOR : "";
            $imageLocation = $this->product->getProductId().'/'.$folder.$this->imageLocation;
        }
        return $imageLocation;
    }

    /**
     * Retrieves all sizes
     *
     * @return mixed
     */
    public function getAllSizes()
    {
        return array(
            'thumbnail'    => $this->getImageLocationBySize('thumbnail'),
            'small'        => $this->getImageLocationBySize('small'),
            'medium'       => $this->getImageLocationBySize('medium'),
            'large'        => $this->getImageLocationBySize('large'),
        );
    }
    
    /**
     * Converts the entity to an array
     *
     * @return fixed
     */
    public function toArray($hasId = true)
    {
        $data = array(
            'raw'               => $this->getImageLocation(true),
            'imageLocation'     => $this->getImageLocation(),
            'fullImageLocation' => $this->getFullImagePath(),
            'isPrimary'         => $this->getIsPrimary(),
            'isDeleted'         => $this->getIsDeleted(),
            'sizes'             => $this->getAllSizes(),
            'defaultLocale'     => $this->getDefaultLocale()
        );

        if($hasId){
            $data['id'] = $this->getProductImageId();
        }

        return $data;
    }

    /**
     * Set defaultLocale
     *
     * @param string $defaultLocale
     * @return ProductImage
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * Get defaultLocale
     *
     * @return string 
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productUnitImages;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productUnitImages = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add productUnitImages
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnitImage $productUnitImages
     * @return ProductImage
     */
    public function addProductUnitImage(\Yilinker\Bundle\CoreBundle\Entity\ProductUnitImage $productUnitImages)
    {
        $this->productUnitImages[] = $productUnitImages;

        return $this;
    }

    /**
     * Remove productUnitImages
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnitImage $productUnitImages
     */
    public function removeProductUnitImage(\Yilinker\Bundle\CoreBundle\Entity\ProductUnitImage $productUnitImages)
    {
        $this->productUnitImages->removeElement($productUnitImages);
    }

    /**
     * Get productUnitImages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProductUnitImages()
    {
        return $this->productUnitImages;
    }
}
