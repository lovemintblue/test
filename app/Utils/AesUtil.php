<?php

declare(strict_types=1);

namespace App\Utils;

class  AesUtil
{
    const KEY = "525202f9149e061d";

    /**
     *
     * @param string $string 需要加密的字符串
     * @param string $key 密钥
     * @return string
     */
    public static function encrypt($string, $key = AesUtil::KEY)
    {
        $data = openssl_encrypt($string, 'AES-128-ECB', hex2bin($key), OPENSSL_RAW_DATA);
        $data = bin2hex($data);
        return $data;
    }

    /**
     * @param string $string 需要解密的字符串
     * @param string $key 密钥
     * @return string
     */
    public static function decrypt($string, $key = AesUtil::KEY)
    {
        try{
            $encrypted = hex2bin($string);
            if(empty($encrypted)){
                return "";
            }
            return openssl_decrypt($encrypted, 'aes-128-ecb', hex2bin($key), OPENSSL_RAW_DATA);
        }catch (\Exception $exception){
            return  "";
        }
    }


    /**
     * 加密
     * @param string $str 要加密的数据
     * @param string $key
     * @return bool|string   加密后的数据
     */
    public static function encryptRaw($str, $key = AesUtil::KEY)
    {
        return openssl_encrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }

    /**
     *
     * 解密
     *
     * @param string $str 要解密的数据
     * @param string $key
     * @return string        解密后的数据
     */
    public static function decryptRaw($str, $key = AesUtil::KEY)
    {
        return openssl_decrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }

    /**
     * 加密
     * @param string $str 要加密的数据
     * @param string $key
     * @return bool|string   加密后的数据
     */
    public static function encryptBase64($str, $key = AesUtil::KEY)
    {
        $result= openssl_encrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        if(empty($result))return null;
        return base64_encode($result);

    }

    /**
     *
     * 解密
     *
     * @param string $str 要解密的数据
     * @param string $key
     * @return string        解密后的数据
     */
    public static function decryptBase64($str, $key = AesUtil::KEY)
    {
        $str = base64_decode($str);
        if(empty($str)) return null;
        return openssl_decrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }
}
