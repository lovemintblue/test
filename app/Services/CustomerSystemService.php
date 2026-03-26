<?php

namespace App\Services;

use App\Constants\CacheKey;
use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Jobs\Center\CenterDataJob;
use App\Utils\AesUtil;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * 客服中心
 * Class
 * @property  CommonService $commonService
 * @property  ConfigService $configService
 * @package App\Services
 */
class CustomerSystemService extends BaseService
{
    /**
     * 获取客服链接
     * @param $userId
     * @param $deviceType
     * @param $deviceVersion
     * @return array
     * @throws \Exception
     */
    public function getUrl($userId, $deviceType, $deviceVersion)
    {
        $configs = CenterDataJob::getCenterConfig('customer');
        $domain  = $configs['url'].'/app/customer';
        $appId   = $configs['appid'];
        $appKey  = $configs['appkey'];

        $sign  = $this->sign([
            'appId'         => $appId,
            'userId'        => $userId,
            'deviceType'    => $deviceType,
            'systemType'    => $deviceType,
            'systemVersion' => $deviceVersion,
        ]);
        $sign = $this->encode($sign,$appKey);

        $url = $domain."/user/getUrl?appId={$appId}&sign={$sign}";
        $result = self::doHttpRequest($url,[]);
        if(empty($result['url'])){
            throw new \Exception("客服系统连接失败");
        }
        return ['url' => $result['url']];
    }

    /**
     * 签名
     * @param $data
     * @return bool|string|null
     */
    private function sign($data)
    {
        ksort($data);
        $items = [];
        foreach ($data as $k => $v) {
            $items[] = $k . '=' . $v;
        }
        $str = implode('&', $items);
        return $str;
    }


    /**
     * 加密
     * @param $str
     * @param $appkey
     * @return string
     */
    public function encode($str,$appkey)
    {
        $iv         = substr($appkey, 0,strlen($appkey));
        // Go 默认使⽤ AES-128-CBC（因为 key ⻓度为 16）
        $row = openssl_encrypt($str, 'AES-128-CBC',$appkey, OPENSSL_RAW_DATA, $iv);
        return bin2hex($row);
    }

    /**
     * 解密
     * @param $str
     * @param $appkey
     * @return false|string
     */
    public function decode($str,$appkey)
    {
        $str        = hex2bin($str);
        $iv         = substr($appkey, 0,strlen($appkey));
        // Go 默认使⽤ AES-128-CBC（因为 key ⻓度为 16）
        $row = openssl_decrypt($str, 'AES-128-CBC', $appkey, OPENSSL_RAW_DATA, $iv);
        return $row;
    }



    /**
     * @param string $requestUrl
     * @param array $requestData
     * @return false|mixed|null
     */
    private static function doHttpRequest(string $requestUrl,array $requestData)
    {
        try{
            $requestData = json_encode($requestData,JSON_UNESCAPED_UNICODE);
            LogUtil::info(sprintf(__CLASS__ . " Request url: %s query:%s", $requestUrl,$requestData));
            $result = CommonUtil::httpGet($requestUrl,3);
            if(empty($result)){
                throw new \Exception("请求错误");
            }
            $result = json_decode($result, true);
            if ($result["code"] != 200){
                throw new \Exception($result['msg']);
            }
            if(empty($result['data'])){
                return null;
            }
            return $result['data'];
        }catch (\Exception $e){
            LogUtil::error(sprintf(__CLASS__.' %s in %s line %s', $e->getMessage(), $e->getFile(), $e->getLine()));
            return false;
        }
    }
}