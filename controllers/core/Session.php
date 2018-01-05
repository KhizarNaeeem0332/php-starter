<?php

namespace Bindeveloperz;


/**
 * Class Session
 * @package Bindeveloperz
 *
 * session_start must be defined
 */
class Session
{

    public static function has($name)
    {
        return (isset($_SESSION[$name])) ? true : false;
    }

    public static function put($name , $value)
    {
        return $_SESSION[$name] = $value;
    }

    public static function get($name)
    {
        return $_SESSION[$name];
    }

    public static function delete($name)
    {
        if(self::has($name))
        {
            unset($_SESSION[$name]);
            session_destroy();
        }
    }

    public static function flash($name , $string = '')
    {
        if(self::has($name))
        {
            $session = self::get($name);
            self::delete($name);
            return $session ;
        }
        else
        {
            self::put($name  , $string);
        }
    }





}