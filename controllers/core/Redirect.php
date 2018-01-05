<?php

namespace Bindeveloperz;


class Redirect
{
    public static function to($location = null , $time = null)
    {
        if($location)
        {
            if(is_numeric($location))
            {
                switch ($location)
                {
                    case 404: {
                        header('HTTP/1.0 404 Not Found');
                        include 'include/errors/404.php';
                        exit();
                        break;
                    }
                }
            }
            if($time != null && is_numeric($time))
            {
                header( "refresh:5;url=" . $location . "");
                exit();
            }
            header('Location: ' . $location);
            exit();
        }//end if
    }//to end

}//class end