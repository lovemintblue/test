<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\AnalysisAdminLogsModel;

/**
 * 管理员日志统计
 * Class AnalysisAdminLogsService
 * @package App\Services
 * @property AnalysisAdminLogsModel $analysisAdminLogsModel
 */
class AnalysisAdminLogsService extends BaseService
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
        return $this->analysisAdminLogsModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->analysisAdminLogsModel->count($query);
    }
}