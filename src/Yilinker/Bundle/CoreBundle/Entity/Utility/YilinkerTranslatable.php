<?php

namespace Yilinker\Bundle\CoreBundle\Entity\Utility;

use Gedmo\Translatable\Translatable;

class YilinkerTranslatable implements Translatable
{
    protected $locale;

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getLocale($default = null)
    {
        return $this->locale ? $this->locale: $default;
    }
}