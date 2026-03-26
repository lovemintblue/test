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
use App\Models\ReportLogModel;
use App\Models\UserOrderModel;
use App\Utils\CommonUtil;
use App\Utils\LogUtil;

/**
 *  report
 * @package App\Services
 *
 * @property  AreaReportModel $areaReportModel
 * @property  ReportLogModel $reportLogModel
 * @property CollectionsModel $collectionsModel
 * @property UserService $userService
 * @property AppLogModel $appLogModel
 * @property MmsService $mmsService
 * @property UserOrderModel $userOrderModel
 * @property CommonService $commonService
 * @property ChannelReportModel $channelReportModel
 */
class ReportService extends BaseService
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
    public function getAreaList($query = array(), $fields = array(), $sort = array(), $skip = 0, $limit = 10)
    {
        return $this->areaReportModel->find($query, $fields, $sort, $skip, $limit);
    }

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
        return $this->reportLogModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->reportLogModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->reportLogModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->reportLogModel->findByID(intval($id));
    }
}