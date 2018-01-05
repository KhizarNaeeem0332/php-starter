<?php

namespace Bindeveloperz;


class Cookie
{

    public static function has($name)
    {
        return (isset($_COOKIE[$name])) ? true : false;
    }

    public static function put($name , $value , $expiry)
    {
        if(setcookie($name , $value ,  strtotime( '+30 days' ) , '/'))
        {
            return true;
        }
        return false;
    }

    public static function get($name)
    {
        return $_COOKIE[$name];
    }

    public static function delete($name)
    {
        self::put($name , ""  , strtotime( '-5 days' ));
    }


}