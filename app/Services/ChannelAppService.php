<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Models\AdvAppModel;
use App\Models\ChannelAppModel;
use App\Utils\LogUtil;

/**
 *  渠道包
 * @package App\Services
 *
 * @property  ChannelAppModel $channelAppModel
 * @property CommonService $commonService
 */
class ChannelAppService extends BaseService
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
        return $this->channelAppModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->channelAppModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->channelAppModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->channelAppModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->channelAppModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->channelAppModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->channelAppModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取所有的apk列表
     * @return array|mixed|null
     */
    public function getAll()
    {
        $keyName = 'channel_app';
        $result = getCache($keyName);
        if ($result === null) {
            $result = array();
            $query = array('is_disabled' => 0);
            $items = $this->getList($query, array(), array('sort' => -1), 0, 5000);
            foreach ($items as $item) {
               // $link = parse_url($item['link'],PHP_URL_PATH);
                $link = $item['link'];
                $result[] = array(
                    'type'                => strval($item['type']),
                    'code'                => strval($item['code']),
                    'link'                => $link,
                    'is_auto_download'    =>empty($item['is_auto_download'])?'n':'y'
                );
            }
            setCache($keyName, $result, 120);
        }
        return empty($result)?array():$result;
    }

    /**
     * 随机获取一个渠道包
     * @param $type
     * @param $isRand
     * @return mixed
     */
    public function getApkByType($type,$isRand=false)
    {
        $result = array();
        $apkArr = $this->getAll();
        foreach ($apkArr as $apk)
        {
            if($apk['type']==$type){
                $result[]= $apk;
            }
        }
        if($isRand){
            return empty($result)?"":$result[mt_rand(0,count($result)-1)]['link'];
        }
        return $result;
    }

}