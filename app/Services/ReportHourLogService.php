<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommonValues;
use App\Core\Services\BaseService;
use App\Models\AppLogModel;
use App\Models\AreaReportModel;
use App\Models\ChannelModel;
use App\Models\ChannelReportModel;
use App\Models\CollectionsModel;
use App\Models\ReportHourLogModel;
use App\Models\ReportLogModel;
use App\Models\UserOrderModel;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 * Class ReportHourLogService
 * @property ReportHourLogModel $reportHourLogModel
 * @package App\Services
 */
class ReportHourLogService extends BaseService
{

    /**
     * 获取列表
     * @param array $query
     * @param array $fields
     * @param array $sort
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function getList($query = array(), $fields = array(), $sort = array(), $skip = 0, $limit = 10)
    {
        return $this->reportHourLogModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->reportHourLogModel->count($query);
    }

}