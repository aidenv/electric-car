<?php

namespace Yilinker\Bundle\CoreBundle\Entity\Traits;

use Yilinker\Bundle\CoreBundle\Entity\LocationType;

trait LocationTrait
{
    public function getCountry()
    {
        if (!$this->getProvince()) {
            return null;
        }

        return $this->getProvince()->getParent();
    }

    public function getProvince()
    {
        if (!$this->getCity()) {
            return null;
        }

        return $this->getCity()->getParent();
    }

    public function getCity()
    {
        if (!$this->getLocation()) {
            return null;
        }

        return $this->getLocation()->getParent();
    }
}
