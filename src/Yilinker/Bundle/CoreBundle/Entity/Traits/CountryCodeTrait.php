<?php

namespace Yilinker\Bundle\CoreBundle\Entity\Traits;

trait CountryCodeTrait
{
    private $country;
    private $countryCode = 'ph';

    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
    }

    public function getCountryCode()
    {
        return $this->countryCode;
    }

    public function setCountry($country)
    {
        $this->country = $country;
    }

    public function getCountry()
    {
        return $this->country;
    }
}