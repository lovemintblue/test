<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommonValues;
use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\AppLogModel;
use App\Models\CollectionsModel;
use App\Models\ReportLogModel;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 *  公共服务类
 * @package App\Services
 *
 * @property ConfigService $configService
 * @property ReportLogModel $reportLogModel
 * @property UserService $userService
 * @property AppLogModel $appLogModel
 * @property CollectionsModel $collectionsModel
 * @property QueueService $queueService
 * @property ApiService $apiService
 * @property CdnService $cdnService
 * @property M3u8Service $m3u8Service
 */
class CommonService extends BaseService
{
    /**
     * 获取配置
     * @param $code
     * @return mixed|null
     */
    public function getConfig($code)
    {
        $result = $this->configService->getAll();
        return isset($result[$code]) ? $result[$code] : null;
    }

    /**
     * 获取配置
     * @return mixed|null
     */
    public function getConfigs()
    {
        return $this->configService->getAll();
    }

    /**
     * 获取支付图标
     * @param $type
     * @return string
     */
    public function getPaymentIco($type)
    {
        $configs = $this->getConfigs();
        $img = '';
        if($type=='alipay'){
            $img = $configs['media_dir'].'/common_file/system/alipay-ico.png';
        }elseif ($type=='wechat'){
            $img = $configs['media_dir'].'/common_file/system/wechat-ico.png';
        }
        return $this->getCdnUrl($img);
    }

    /**
     * 获取m3u8链接
     * @param $link
     * @param string $cdnType
     * @param string $source
     * @return string
     */
    public function getVideoCdnUrl($link,$cdnType='default',$source='media')
    {
        return $this->m3u8Service->encode($link,$this->apiService->getDeviceType(),$cdnType,$source);
    }

    /**
     * 获取cdn链接
     * @param  $link
     * @param $type
     * @param $cdnType
     * @return string
     */
    public function getCdnUrl($link, $type = 'image',$cdnType='default')
    {
        return $this->cdnService->getCdnUrl($link,$type,$cdnType);
    }

    /**
     * 获取计数器
     * @param $keyName
     * @return float|int
     */
    public function getRedisCounter($keyName)
    {
        $count = $this->getRedis()->get($keyName);
        return $count * 1;
    }

    /**
     * 计数器解决n*1问题
     * @param $keys
     * @return array
     */
    public function getRedisCounters($keys)
    {
        $vals = $this->getRedis()->mget($keys);

        $map = [];
        foreach ($keys as $i => $k) {
            $v = $vals[$i] ?? false;
            $map[$k] = $v !== false ? (int)$v : 0;
        }
        return $map; // key => int
    }

    /**
     * 更新设置计数器
     * @param $keyName
     * @param null $value
     * @param integer $timeout
     * @return float|int
     */
    public function setRedisCounter($keyName, $value, $timeout = null)
    {
        $this->getRedis()->set($keyName, $value, $timeout);
        return $value * 1;
    }

    /**
     * 更新设置计数器
     * @param $keyName
     * @param null $value
     * @return float|int
     */
    public function updateRedisCounter($keyName, $value)
    {
        $value = $this->getRedis()->incrBy($keyName, $value);
        return $value * 1;
    }

    /**
     * 限流动作检查
     * @param $keyName
     * @param int $seconds
     * @param int $num
     * @return bool
     */
    public function checkActionLimit($keyName, $seconds = 60, $num = 3)
    {
        $count = $this->getRedis()->incrBy($keyName, 1);
        if ($count == 1) {
            $this->getRedis()->expire($keyName, $seconds);
        }
        return $count > $num ? false : true;
    }

    /**
     * 取消限流
     * @param $keName
     * @return bool
     */
    public function unlockActionLimit($keName)
    {
        $this->getRedis()->delete($keName);
        return true;
    }

    /**
     * 导出excel
     * @param $cells
     * @param $data
     * @param $excelName
     * @return string|string[]
     * @throws BusinessException
     */
    public function exportExcel($cells, $data, $excelName)
    {
        $runtimePath = CommonUtil::getPublicRuntimePath();
        $excelPath = $runtimePath . '/' . date('Y-m-d');
        if (!file_exists($excelPath)) {
            mkdir($excelPath, 0777, true);
        }
        $excelPath .= '/' . CommonUtil::getId() . '.xlsx';
        $newExcel = new Spreadsheet();  //创建一个新的excel文档
        $objSheet = $newExcel->getActiveSheet();  //获取当前操作sheet的对象
        $objSheet->setTitle($excelName);  //设置当前sheet的标题

        $filedNames = array_values($cells);
        $countCel = count($filedNames);
        for ($i = 0; $i < $countCel; $i++) {
            $cellName = $this->intToExcelChr($i);
            $objSheet->getColumnDimension($cellName)->setAutoSize(true);
            $objSheet->setCellValue($cellName . '1', $filedNames[$i]);
        }

        foreach ($data as $index => $item) {
            $k = $index + 2;
            $fields = array_keys($cells);
            foreach ($fields as $fieldIndex => $fieldName) {
                $cellName = $this->intToExcelChr($fieldIndex);
                $objSheet->setCellValue($cellName . $k, strval($item[$fieldName]));
            }
        }

        try {
            $objWriter = IOFactory::createWriter($newExcel, 'Xlsx');
            $objWriter->save($excelPath);
            return str_replace(WEB_PATH, '', $excelPath);
        } catch (\Exception $exception) {
            LogUtil::error($exception);
        }
        throw  new BusinessException(StatusCode::DATA_ERROR, '导出数据错误!');
    }

    /**
     * 数字转excel列名
     * @param $index
     * @param int $start
     * @return string
     */
    public function intToExcelChr($index, $start = 65)
    {
        $str = '';
        if (floor($index / 26) > 0) {
            $str .= $this->intToExcelChr(floor($index / 26) - 1);
        }
        return $str . chr($index % 26 + $start);
    }


    /**
     * 获取mongo的状态
     * @return mixed
     */
    public function getMongoStatus()
    {
        try {
            return $this->reportLogModel->count();
        }catch (\Exception $e){
            return 0;
        }
    }

    /**
     * 清除缓存
     */
    public function clearCache()
    {
        try {
            $config = container()->get('config');
            $config = $config->cache;
            $adapter = $config->adapter;
            if ($adapter == 'redis') {
                $timeout = 2.5;
                $redis = new \Redis();
                $redis->pconnect($config->host, $config->port * 1, $timeout);
                $redis->select($config->index * 1);
                $redis->flushDB();
                $redis->close();
            } else {
                container()->get('cache')->clear();
            }
        } catch (\Exception $exception) {
            LogUtil::error($exception);
        }
    }

    public function getUploadImageUrl($configs=null)
    {
        $configs = empty($configs)?$this->getConfigs():$configs;
        return sprintf('%s/upload/image?key=%s',$configs['upload_url'],$configs['media_key']);
    }

    public function getUploadFileUrl($configs=null)
    {
        $configs = empty($configs)?$this->getConfigs():$configs;
        return sprintf('%s/upload/byte?key=%s',$configs['upload_url'],$configs['media_key']);
    }

    public function getUploadFileQueryUrl($configs=null)
    {
        $configs = empty($configs)?$this->getConfigs():$configs;
        return sprintf('%s/upload/query?key=%s',$configs['upload_url'],$configs['media_key']);
    }

}