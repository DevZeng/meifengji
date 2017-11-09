<?php
/**
 * Created by PhpStorm.
 * User: devzeng
 * Date: 17-11-3
 * Time: 下午1:53
 */
if (!function_exists('createNoncestr')){
    function createNoncestr($length = 8) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}
if (!function_exists('setUserToken')){
    function setUserToken($key,$value)
    {
        \Illuminate\Support\Facades\Redis::set($key,$value);
    }
}
if (!function_exists('getUserToken')) {
    function getUserToken($key)
    {
        $uid = \Illuminate\Support\Facades\Redis::get($key);
        if (!isset($uid)){
            return false;
        }
        return $uid;
    }
}
if (!function_exists('formatUrl')) {
    function formatUrl($url)
    {
        return env('APP_URL').$url;
//        return 'http://119.23.255.177:8090/'.$url;
    }
}
if (!function_exists('setCode')){
    function setCode($key,$value)
    {
        \Illuminate\Support\Facades\Redis::set($key,$value);
        \Illuminate\Support\Facades\Redis::expire($key,900);

    }
}
if (!function_exists('getCode')) {
    function getCode($phone)
    {
        $code = \Illuminate\Support\Facades\Redis::get($phone);
        if (!isset($code)){
            return false;
        }
        return $code;
    }
}
if (!function_exists('getRandCode')){
    function getRandCode($length = 6)
    {
        return rand(pow(10,($length-1)), pow(10,$length)-1);
    }
}