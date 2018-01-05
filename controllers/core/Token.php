<?php

namespace Bindeveloperz;
use Bindeveloperz\Session as Session;

class Token
{

    public static function generate($name = '_token')
    {
        return Session::put( "$name" , md5(uniqid()));
    }

    public static function check($token)
    {
        $tokenName = '_token';

        if(Session::has($tokenName) && $token === Session::get($tokenName))
        {
            Session::delete($tokenName);
            return true;
        }
        return false;
    }


}//class end