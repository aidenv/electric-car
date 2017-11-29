<?php

namespace Yilinker\Bundle\CoreBundle\Services\Redis;

use Endroid\QrCode\QrCode;
use Symfony\Component\HttpFoundation\File\File;
use Gaufrette\Adapter\AwsS3;

class Keys
{
    const HOME_DATA = "home-data";
    const HOME_PRODUCT_SECTION = "home-product-section";
    const SHIPPING_CATEGORIES = "shipping-categories";
    const PRODUCT_CONDITIONS = "product-conditions";
    const BRANDS = "product-conditions";
    const HOME_BACK_TO_SCHOOL_PROMO = "back-to-school-promo";
}
