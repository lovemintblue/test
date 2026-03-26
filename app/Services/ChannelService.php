<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\ChannelModel;

/**
 * 渠道管理
 * @package App\Services
 *
 * @property  ChannelModel $channelModel
 */
class ChannelService extends BaseService
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
        return $this->channelModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->channelModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->channelModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->channelModel->findByID($id);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->channelModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->channelModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->channelModel->delete(array('_id' => $id));
    }

    /**
     * 获取所有渠道
     * @param int $day
     * @return array
     */
    public function getAll($day=1)
    {
        $result =[];
        $query = ['is_disabled' => 0];
        if($day){
            $query['last_bind'] = ['$gte'=>strtotime("-{$day}day")];
        }
        $count = $this->count($query);
        $pageSize = 500;
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $skip = ($page - 1) * $pageSize;
            $items = $this->getList($query,[],[],$skip,$pageSize);
            foreach ($items as $item) {
                $result[]=$item;
            }
        }
        return $result;
    }


    /**
     * 绑定渠道
     * @param $channelName
     */
    public function bindChannel($channelName)
    {
        if(strlen($channelName)>20){
            return;
        }
        $channelId = md5($channelName);
        $result = $this->findByID($channelId);
        if ($result) {
            $this->save([
                '_id' => $channelId,
                'last_bind' => time()
            ]);
        } else {
            $this->channelModel->insert(array(
                '_id' => $channelId,
                'code' => $channelName,
                'name' => $channelName,
                'is_disabled' => 0,
                'last_bind' => time()
            ));
        }
    }

}