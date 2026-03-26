<?php

declare(strict_types=1);

namespace App\Repositories\Backend;

use App\Core\Repositories\BaseRepository;
use App\Exception\BusinessException;
use App\Services\CommonService;
use App\Services\ConfigService;

/**
 * 公共仓库
 * Class CommonRepository
 * @package App\Repositories\Backend
 * @property CommonService $commonService
 * @property ConfigService $configService
 */
class CommonRepository extends BaseRepository
{

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
        return $this->commonService->exportExcel($cells,$data,$excelName);
    }

    /**
     * 导出数据最大条数
     * @return int
     */
    public function  getExportMaxSize()
    {
        return 20000;
    }

    /**
     * 获取配置
     * @return array
     */
    public function getConfigs()
    {
        return $this->configService->getAll();
    }

    /**
     * 获取指定配置
     * @param $code
     * @return mixed|null
     */
    public function getConfig($code)
    {
        return $this->configService->getConfig($code);
    }

}