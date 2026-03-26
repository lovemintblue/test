<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Utils\LogUtil;

/**
 * 视频销售
 * Class MssService
 * @property MovieService $movieService
 * @property UserBuyLogService $userBuyLogService
 * @package App\Services
 */
class MssService extends BaseService
{

    /**
     * 加密
     * @param string $str 要加密的数据
     * @param string $key
     * @return bool|string   加密后的数据
     */
    public function encryptRaw($str, $key)
    {
        return openssl_encrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }

    /**
     * 解密
     * @param string $str 要解密的数据
     * @param string $key
     * @return string        解密后的数据
     */
    public function decryptRaw($str, $key)
    {
        return openssl_decrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }


    /**
     * 网络请求
     * @param $path
     * @param $data
     * @return mixed|null
     */
    public function doRequest($path, $data)
    {
        $config = container()->get('config');
        if (!isset($config->mss->url) || !isset($config->mss->app_id) || !isset($config->mss->app_key) || !isset($config->mss->common_key)) {
            LogUtil::error('Please config mss!');
            return null;
        }
        $data = array('data' => $data);
        $data = json_encode($data);
        $data = $this->encryptRaw($data, $config->mss->app_key);
        $header = array(
            'version:1.0',
            'time:'.date('Y-m-d H:i:s'),
            'appid:'.$config->mss->app_id
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config->mss->url . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = empty($result) ? null : $this->decryptRaw($result, $config->mss->common_key);
        if (empty($result)) {
            LogUtil::error('Network error!');
            return null;
        }
        $result = json_decode($result, true);
        if ($result['status'] == 'y') {
            return $result['data'];
        }
        LogUtil::error($result['error']);
        return null;
    }

}