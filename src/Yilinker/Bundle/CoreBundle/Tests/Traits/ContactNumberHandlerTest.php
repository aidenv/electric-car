<?php

namespace Yilinker\Bundle\CoreBundle\Tests\Traits;

use Yilinker\Bundle\CoreBundle\Tests\YilinkerCoreWebTestCase;
use Yilinker\Bundle\CoreBundle\Traits\ContactNumberHandler;
use Yilinker\Bundle\CoreBundle\Entity\Country;

class ContactNumberHandlerTest extends YilinkerCoreWebTestCase
{
	use ContactNumberHandler;

    public function validLengthContactNumberDataProvider()
    {
        return array(
            array(
                array(
                    "countryCode" => Country::COUNTRY_CODE_PHILIPPINES,
                    "contactNumber" => "09056671106"
                )
            ),
            array(
                array(
                    "countryCode" => Country::COUNTRY_CODE_PHILIPPINES,
                    "contactNumber" => "9056671106"
                )
            )
        );
    }

    /**
     * @dataProvider validLengthContactNumberDataProvider
     * @param array $data
     */
    public function testFormatContactNumberSuccess($data)
    {
    	$contactNumber = $this->formatContactNumber($data["countryCode"], $data["contactNumber"]);

    	if($data["countryCode"] == Country::COUNTRY_CODE_PHILIPPINES){
    		$this->assertSame(11, strlen($contactNumber));
    	}
    }

    public function invalidDataProvider()
    {
        return array(
            array(
            	//invalid contact number length
                array(
                    "countryCode" => Country::COUNTRY_CODE_PHILIPPINES,
                    "contactNumber" => "056671106"
                )
            ),
            array(
            	//invalid country code
                array(
                    "countryCode" => "RANDOMCOUNTRY",
                    "contactNumber" => "9056671106"
                )
            )
        );
    }

    /**
     * @dataProvider invalidDataProvider
     * @param array $data
     */
    public function testFormatContactNumberFail($data)
    {
    	$contactNumber = $this->formatContactNumber($data["countryCode"], $data["contactNumber"]);

		$this->assertSame($data["contactNumber"], $contactNumber);
    }
}

