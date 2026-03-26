<?php

namespace App\Repositories\Backend;

use App\Core\Repositories\BaseRepository;
use App\Constants\CommonValues;
use App\Exception\BusinessException;
use App\Constants\StatusCode;
use App\Services\JobService;

/**
 * 任务
 * Class MovieRepository
 * @property JobService $jobService
 */
class JobRepository extends BaseRepository
{
    /**
     * 获取列表
     * @param $request
     * @return array
     */
    public function getList($request)
    {
        $page       = $this->getRequest($request, 'page', 'int', 1);
        $pageSize   = $this->getRequest($request, 'pageSize', 'int', 15);
        $order      = $this->getRequest($request, 'order', 'string', '_id');
        $query  = array();
        $filter = array();
        $sort = [
            $order => -1,
        ];

        if (isset($request['status']) && $request['status']!=="") {
            $filter['status'] = $this->getRequest($request, 'status','int', 0);
            $query['status']  = $filter['status'];
        }
        if (isset($request['level']) && $request['level']!=="") {
            $filter['level'] = $this->getRequest($request, 'level','string');
            $query['level']  = $filter['level'];
        }

        $skip   = ($page - 1) * $pageSize;
        $fields = array();
        $count  = $this->jobService->count($query);
        if($count){
            $items  = $this->jobService->getList($query, $fields, $sort, $skip, $pageSize);
            foreach ($items as $index => $item) {
                $item['id']           = $item['_id'];
                $item['status_text']  = CommonValues::getJobStatus(intval($item['status']));
                $item['level_text']  = CommonValues::getJobLevel($item['level']);
                $item['created_at']   = date('Y-m-d H:i', $item['created_at']);
                $item['updated_at']   = date('Y-m-d H:i', $item['updated_at']);
                $item['failed_at']    = $item['failed_at']?date('Y-m-d H:i', $item['failed_at']):'-';

                $items[$index] = $item;
            }
        }
        return array(
            'filter' => $filter,
            'items' => empty($items) ? array() : array_values($items),
            'count' => $count,
            'page' => $page,
            'pageSize' => $pageSize
        );
    }

    /**
     * 获取单条数据
     * @param $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->jobService->findByID($id);
    }

    /**
     * 更新数据
     * @param $data
     * @return bool|int|mixed
     */
    public function update($data)
    {
        return $this->jobService->save($data);
    }

}