<?php

namespace Yilinker\Bundle\CoreBundle\Traits;

use Yilinker\Bundle\CoreBundle\Entity\Country;

trait ContactNumberHandler
{
    public function formatContactNumber($countryCode, $contactNumber)
    {
        switch($countryCode){
            case Country::COUNTRY_CODE_PHILIPPINES:
                if(strlen($contactNumber) == 10){
                    return "0".$contactNumber;
                }
                break;
        }

        return $contactNumber;
    }
}