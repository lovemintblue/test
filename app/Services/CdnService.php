<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Utils\CommonUtil;

class CdnService extends BaseService
{
    const AWS_KEY_ID  = 'KWF4GWYYHU2MK';
    const AWS_RSA_KEY = APP_PATH.'/Resource/ssl/private_key.pem';
    const TENCENT_KEY = 'sahajin281saas1qassasasa9222soun';
    /**
     *  rsa sha1签名
     * @param $policy
     * @param $private_key_filename
     * @return string
     */
    protected function getRsaSha1Sign($policy, $private_key_filename)
    {
        $signature = "";
        // load the private key
        $fp = fopen($private_key_filename, "r");
        $priv_key = fread($fp, 8192);
        fclose($fp);
        $pkeyid = openssl_get_privatekey($priv_key);
        // compute signature
        openssl_sign($policy, $signature, $pkeyid);
        // free the key from memory
        openssl_free_key($pkeyid);
        return $signature;
    }

    /**
     *  url base64
     * @param $value
     * @return mixed
     */
    protected function getUrlSafeBase64Encode($value)
    {
        $encoded = base64_encode($value);
        // replace unsafe characters +, = and / with the safe characters -, _ and ~
        return str_replace(
            array('+', '=', '/'),
            array('-', '_', '~'),
            $encoded);
    }

    /**
     * encode参数 主要用于js和swf
     * @param $stream_name
     * @return mixed
     */
   protected function getEncodeQueryParams($stream_name)
   {
        // Adobe Flash Player has trouble with query parameters being passed into it,
        // so replace the bad characters with their URL-encoded forms
        return str_replace(
            array('?', '=', '&'),
            array('%3F', '%3D', '%26'),
            $stream_name);
    }

    /**
     * 获取aws cdn签名url
     * @param  string $cdnUrl cdn域名
     * @param  string $link 路径带域名
     * @param  int $expires 过期时间  格林威治时间
     * @return mixed
     */
   public  function getAwsUrl($cdnUrl,$link, $expires=null)
   {
       $ext =  CommonUtil::getFileExtName($link);
       if(in_array($ext, array('.jpg','.jpeg','.png','.bmp','.gif','.txt','.webp'))){
           $link = str_replace($ext, '.bnc', $link);
       }
       $needCache = strpos($link,'.bnc')>0?true:false;
       $cacheKey  = md5($link);
       if($needCache){
           $cdnLink = getCache($cacheKey);
           if(!empty($cdnLink) && strpos($cdnLink,$cdnUrl)!==false){
               return $cdnLink;
           }
       }
       $link = $cdnUrl.$link;
       $expires = empty($expires)?time()+3600*6:$expires;
       $canned_policy = '{"Statement":[{"Resource":"' . $link . '","Condition":{"DateLessThan":{"AWS:EpochTime":'. $expires . '}}}]}';
       $signature = $this->getRsaSha1Sign($canned_policy, self::AWS_RSA_KEY);
       $encoded_signature = $this->getUrlSafeBase64Encode($signature);
       $separator = strpos($link, '?') == FALSE ? '?' : '&';
       $link .=  $separator . "Expires=" . $expires . "&Signature=" . $encoded_signature . "&Key-Pair-Id=" . self::AWS_KEY_ID;
       // new lines would break us, so remove them
       $cdnLink = str_replace('\n', '', $link);
       if($needCache){
           setCache($cacheKey,$cdnLink,1*3600);
       }
       return $cdnLink;
    }

    /**
     * 获取腾讯的签名链接 实际上是橙子云
     * @param $link
     * @return string
     */
    public function getTencentUrl($link)
    {
        $ext =  CommonUtil::getFileExtName($link);
        if(in_array($ext, array('.jpg','.jpeg','.png','.bmp','.gif','.txt','.webp'))){
            $link = str_replace($ext, '.bnc', $link);
            return $link;
        }
        $time=strtotime(date('Y-m-d H:i:s'));
        $rand = md5(strval($time));
        $uid = 0;
        $md5 = md5($link.'-'.$time.'-'.$rand.'-'.$uid.'-'.self::TENCENT_KEY);
        $sign= $time.'-'.$rand.'-'.$uid.'-'.$md5;
        $link .= '?sign='.$sign;
        return $link;
    }

    /**
     * 获取腾讯的签名链接 实际上是橙子云
     * @param $link
     * @return string
     */
    public function getDefaultUrl($link)
    {
        $ext =  CommonUtil::getFileExtName($link);
        if(in_array($ext, array('.jpg','.jpeg','.png','.bmp','.gif','.txt','.webp'))){
            $link = str_replace($ext, '.bnc', $link);
        }
        return $link;
    }

    /**
     * 老司机签名链接
     * @param $cdnUrl
     * @param $link
     * @return string
     */
    public function getLsjUrl($cdnUrl,$link)
    {
        $time = time();
        $rand = md5(strval(microtime(true)));
        $config=container()->get('config');
        $sign= md5($time.'-'.$rand.'-'.$config->mrs->laosiji_api_key);
        $expired = 3*3600;
        $uid = $configs['upload_user_id']??-1;
        return $cdnUrl.$link.'?sign='.$sign.'&time='.$time.'&rand='.$rand.'&uid='.$uid;
    }

    /**
     * 获取cdn链接
     * @param  $link
     * @param  string $contentType  image|video
     * @param  string $cdnType  default|vip|overseas
     * @return string
     */
    public function getCdnUrl($link, $contentType = 'image',$cdnType='default')
    {
        if(empty($link) || strpos($link,'://')>0){
            return $link;
        }
        if(strpos($link, "media://")!==false){
            $link  = str_replace('media://', '', $link);
        }
        $configs = getConfigs();
        $cdnDrive = $configs['cdn_drive_'.$contentType.'_'.$cdnType];
        $cdnUrl = $configs['cdn_'.$contentType.'_'.$cdnType];
        if(empty($cdnUrl) || empty($cdnDrive)){
            return '';
        }
        switch ($cdnDrive)
        {
            case 'tencent':
                $fullLink =$cdnUrl.$this->getTencentUrl($link);
                break;
            case 'aws':
                $fullLink =$this->getAwsUrl($cdnUrl,$link);
                break;
            default:
                $fullLink = $cdnUrl.$this->getDefaultUrl($link);
                break;
        }
        return $fullLink;
    }

}