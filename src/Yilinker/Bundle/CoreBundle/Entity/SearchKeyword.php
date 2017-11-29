<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationType
 */
class SearchKeyword
{
    /**
     * @var integer
     */
    private $searchKeywordId;

    /**
     * @var string
     */
    private $keyword;

    /**
     * Get searchKeywordId
     *
     * @return integer 
     */
    public function getSearchKeywordId()
    {
        return $this->searchKeywordId;
    }

    /**
     * Set keyword
     *
     * @param string $keyword
     * @return SearchKeyword
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * Get keyword
     *
     * @return string 
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

}
