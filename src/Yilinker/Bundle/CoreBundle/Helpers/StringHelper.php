<?php

namespace Yilinker\Bundle\CoreBundle\Helpers;

/**
 * Class StringHelper
 *
 * @package Yilinker\Bundle\CoreBundle\Helpers
 */
class StringHelper
{

    /**
     * Generate Random string
     *
     * @param $length
     * @param bool|true $withLetters
     * @param bool|true $withNumbers
     * @return string
     */
    public static function generateRandomString($length, $withLetters = true, $withNumbers = true)
    {
        $result = '';
        if ($withLetters && !$withNumbers) {
            $characters = range('a','z');
            $max = count($characters) - 1;
            for ($i = 0; $i < $length; $i++) {
                $rand = mt_rand(0, $max);
                $result .= $characters[$rand];
            }
        }
        else if (!$withLetters && $withNumbers) {
            for($i = 0; $i < $length; $i++) {
                $result .= mt_rand(0, 9);
            }
        }
        else {
            $characters = array_merge(range('a','z'), range(0, 9));
            $max = count($characters) - 1;
            for ($i = 0; $i < $length; $i++) {
                $rand = mt_rand(0, $max);
                $result .= $characters[$rand];
            }
        }

        return $result;
    }

}
