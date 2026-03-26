<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\ChannelReportModel;

/**
 * 渠道报表
 * @package App\Services
 *
 * @property  ChannelReportModel $channelReportModel
 */
class ChannelReportService extends BaseService
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
        return $this->channelReportModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->channelReportModel->count($query);
    }

    /**
     * @param $pipeline
     * @return mixed
     */
    public function sum($pipeline)
    {
        return $this->channelReportModel->aggregate($pipeline);
    }

    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->channelReportModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->channelReportModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->channelReportModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->channelReportModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->channelReportModel->delete(array('_id' => intval($id)));
    }

    /**
     * 记录独立ip数
     * @param $channelName
     * @param $ip
     * @param $date
     * @return void
     */
    public function doIP($channelName, $ip, $date = null)
    {
        $date = $date ?? date('Y-m-d');
        //所有
        $keyName = "report_channel_ip:_all:" . $date;
        $this->getRedis()->pfAdd($keyName, [$ip]);
        $this->getRedis()->expire($keyName, 86400 * 31);

        //某个渠道
        if (!empty($channelName) && $channelName != '_all') {
            $keyName = "report_channel_ip:{$channelName}:" . $date;
            $this->getRedis()->pfAdd($keyName, [$ip]);
            $this->getRedis()->expire($keyName, 86400 * 31);
        }

    }

    /**
     * 获取独立ip计数
     * @param $channelName
     * @param $date
     * @return int
     */
    public function getIPCount($channelName, $date)
    {
        $keyName = "report_channel_ip:{$channelName}:" . $date;
        return intval($this->getRedis()->pfcount($keyName));
    }

    /**
     * 记录独立用户数
     * @param $channelName
     * @param $userId
     * @param $date
     * @return void
     */
    public function doUV($channelName, $userId, $date = null)
    {
        $date = $date ?? date('Y-m-d');
        //所有
        $keyName = "report_channel_uv:_all:" . $date;
        $this->getRedis()->pfAdd($keyName, [$userId]);
        $this->getRedis()->expire($keyName, 86400 * 31);

        //某个渠道
        if (!empty($channelName) && $channelName != '_all') {
            $keyName = "report_channel_uv:{$channelName}:" . $date;
            $this->getRedis()->pfAdd($keyName, [$userId]);
            $this->getRedis()->expire($keyName, 86400 * 31);

        }
    }

    /**
     * 获取记录独立用户数
     * @param $channelName
     * @param $date
     * @return int
     */
    public function getUVCount($channelName, $date)
    {
        $keyName = "report_channel_uv:{$channelName}:" . $date;
        return intval($this->getRedis()->pfcount($keyName));
    }

    /**
     * 记录页面数
     * @param $channelName
     * @param $date
     * @return void
     */
    public function doPV($channelName, $date = null)
    {
        $date = $date ?? date('Y-m-d');
        //所有
        $keyName = "report_channel_pv:_all:" . $date;
        $this->getRedis()->incr($keyName);
        $this->getRedis()->expire($keyName, 86400 * 31);

        //某个渠道
        if (!empty($channelName) && $channelName != '_all') {
            $keyName = "report_channel_pv:{$channelName}:" . $date;
            $this->getRedis()->incr($keyName);
            $this->getRedis()->expire($keyName, 86400 * 31);
        }
    }


    /**
     * 获取页面计数
     * @param $channelName
     * @param $date
     * @return int
     */
    public function getPVCount($channelName, $date)
    {
        $keyName = "report_channel_pv:{$channelName}:" . $date;
        return intval($this->getRedis()->get($keyName) ?? 0);
    }

    /**
     * 记录浏览数,movie comics novel post audio....
     * @param $channelName
     * @param $date
     * @return void
     */
    public function doView($channelName, $date = null)
    {
        $date = $date ?? date('Y-m-d');

        //所有
        $keyName = "report_channel_view:_all:" . $date;
        $this->getRedis()->incr($keyName);
        $this->getRedis()->expire($keyName, 86400 * 31);

        //某个渠道
        if (!empty($channelName) && $channelName != '_all') {
            $keyName = "report_channel_view:{$channelName}:" . $date;
            $this->getRedis()->incr($keyName);
            $this->getRedis()->expire($keyName, 86400 * 31);
        }
    }

    /**
     * 获取浏览数计数
     * @param $channelName
     * @param $date
     * @return int
     */
    public function getViewCount($channelName, $date)
    {
        $keyName = "report_channel_view:{$channelName}:" . $date;
        return intval($this->getRedis()->get($keyName) ?? 0);
    }

}