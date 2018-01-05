<?php


/*
 * TODO : checkIsset($value, $default = "", $from = "", $type = 'checked')
 * TODO:  checkSelectIsset($value, $from = "", $default = "", $type = 'selected')
 * TODO: countryList()
 */



namespace Bindeveloperz;

class Stringer
{

    public static $encoding = 'UTF-8';


    public static function lower($str) {
        return strtolower(trim($str));
    }

    public static function upper($str) {
        return strtoupper(trim($str));
    }

    public static function initCap($str) {
        return ucwords(trim($str));
    }

    public static function uFirst($str) {
        return ucfirst(trim($str));
    }

    public static function nvl($value, $ifnull) {
        if ($value == null) {
            return $ifnull;
        } else {
            return $value;
        }
    }

    public static function convertNumToRoman($number , $lowerUpper = true)
    {

        $table = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
        $return = '';
        while ($number > 0) {
            foreach ($table as $rom => $arb) {
                if ($number >= $arb) {
                    $number -= $arb;
                    $return .= ($lowerUpper) ? strtolower($rom) : $rom;
                    break;
                }
            }
        }
        return $return;
    }

}//Stringer class end





