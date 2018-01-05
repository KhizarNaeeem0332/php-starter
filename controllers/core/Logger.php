<?php

/*
 * TODO : alert function for bootstrap alert
 */


namespace Bindeveloperz;

class Logger
{

    public static function dd($var , $msg = null) {

        echo "<pre>";
        if(!$msg == null)
        {
            echo $msg . "<br>";
        }
        print_r($var);
        echo "</pre>";
    }


    public static function ddd($var , $msg = null) {

        echo "<pre>";
        if($msg != null) {
            echo $msg . ": ";
        }
        print_r($var);
        echo "</pre>";
        die();
    }




}