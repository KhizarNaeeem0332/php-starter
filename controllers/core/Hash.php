<?php

namespace Bindeveloperz;


class Hash
{


    public function hashPassword($password)
    {
        return md5($password);
    }

    public function checkPassword($password , $hashPassword)
    {
        if($this->hashPassword($password) === $hashPassword)
        {
            return true ;
        }
        return false;
    }


    public static function encrypt( $string , $secretKey = null ) {
        // you may change these values to your own

        if($secretKey == null) {
            return "";
        }

        $secret_key = $secretKey;
        $secret_iv = $secretKey;

        $output = "";
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $secret_key );
        $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );
        $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
        return $output;
    }



    public static function decrypt( $string , $secretKey = null ) {
        // you may change these values to your own

        if($secretKey == null) {
            return "";
        }

        $secret_key = $secretKey;
        $secret_iv = $secretKey;

        $output = "";
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $secret_key );
        $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );
        $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
        return $output;
    }







}