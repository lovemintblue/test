<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Utils\LogUtil;

/**
 * IP解析
 * @package App\Services
 * @property CommonService $commonService
 */
class IpService extends BaseService
{

    /**
     * 导入ip库
     * @throws BusinessException
     */
    public function init()
    {
        ini_set('memory_limit', '512M');
        $ipFile = BASE_PATH . '/app/Resource/ips/ips.txt';
        if (!file_exists($ipFile)) {
            throw new BusinessException(StatusCode::DATA_ERROR, "Please unzip ips config!");
        }
        $redisService = $this->commonService->getRedis();
        $redis = $redisService->getRedis();
        //批量导入 更快
        $redis->multi(2);
        $redis->del($redisService->getKey('qqzengip'));
        //批量导入
        $handle = fopen($ipFile, 'r+');
        $i = 0;
        while (($line = fgets($handle)) !== false) {
            $line = str_replace(array("\n", "\r"), "", $line);
            list($start, $end, $startnum, $endnum, $continent, $country, $province, $city, $district, $isp, $areacode, $en, $cc, $lng, $lat) = explode("|", $line);
            $redis->zAdd($redisService->getKey('qqzengip'), intval($endnum), $line);
            $i++;
            if ($i % 10000 == 0) {
                LogUtil::info('Import num:' . $i);
            }
        }
        fclose($handle);
        $redis->exec();
        $count = $redis->zCount($redisService->getKey('qqzengip'), "-inf", "+inf");
        $maxNum = 4294967295;
        $result = $redis->zRangeByScore($redisService->getKey('qqzengip'), strval($maxNum), strval($maxNum), array('limit' => array(0, 1)));
        $version = $result[0];
        LogUtil::info(sprintf('Import  ip count:%s ,version: %s', $count, $version));
    }

    /**
     * 解析ip
     * @param $ip
     * @return mixed
     */
    public function parse($ip)
    {
        try {
            $redisService = $this->commonService->getRedis();
            $redis = $redisService->getRedis();
            $ipnum = ip2long($ip);
            $result = $redis->zRangeByScore($redisService->getKey('qqzengip'), strval($ipnum), "inf", array("limit" => array(0, 1)));
            if (empty($result)) return null;
            $k = array_keys($result);
            $k = $k[0];
            $areaInfo = $result[$k];
            $areaInfo = explode("|", $areaInfo);
            return array(
                'country' => $areaInfo[5],
                'province' => $areaInfo[6],
                'city' => $areaInfo[7],
                'district' => $areaInfo[8],
                'isp' => $areaInfo[9],
                'areacode' => $areaInfo[10],
                'en' => $areaInfo[11],
                'cc' => $areaInfo[12],
                'lng' => $areaInfo[13],
                'lat' => $areaInfo[14]
            );
        } catch (\Exception $exception) {

        }
        return null;
    }

    /**
     * 解析格式化后的省份
     * @param $ip
     * @return mixed|string
     */
    public function parseProvince($ip)
    {
        $areaInfo = $this->parse($ip);
        if (empty($areaInfo) || $areaInfo['country'] == '保留') {
            return '';
        }
        if ($areaInfo['country'] != '中国') {
            return $areaInfo['country'];
        }
        return $areaInfo['province'];
    }

    /**
     * 解析格式化后的城市
     * @param $ip
     * @return mixed|string
     */
    public function parseProvinceAndCity($ip)
    {
        $areaInfo = $this->parse($ip);
        if (empty($areaInfo) || $areaInfo['country'] == '保留') {
            return '';
        }
        if ($areaInfo['country'] != '中国') {
            return $areaInfo['country'];
        }
        return $areaInfo['province'].$areaInfo['city'];
    }

    /**
     * 获取省份
     * @param $areaInfo
     * @return string
     */
    public function getProvince($areaInfo)
    {
        if (empty($areaInfo) || $areaInfo['country'] == '保留') {
            return '';
        }
        if ($areaInfo['country'] != '中国') {
            return $areaInfo['country'];
        }
        return $areaInfo['province'];
    }

    /**
     * 获取省份
     * @param $areaInfo
     * @return string
     */
    public function getProvinceAndCity($areaInfo)
    {
        if (empty($areaInfo) || $areaInfo['country'] == '保留') {
            return '';
        }
        if ($areaInfo['country'] != '中国') {
            return $areaInfo['country'];
        }
        return $areaInfo['province'].' ' .$areaInfo['city'];
    }


    /**
     * 解析是否中国地区
     * @param $info
     * @return bool
     */
    public function isChina($info)
    {
        if (!is_array($info)) {
            $info = $this->parse($info);
        }
        if (strpos($info['country'], '中国') !== false) {
            return true;
        }
        return false;
    }
}