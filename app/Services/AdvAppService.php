<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Models\AdvAppModel;

/**
 *  应用中心
 * @package App\Services
 *
 * @property  AdvAppModel $advAppModel
 * @property CommonService $commonService
 */
class AdvAppService extends BaseService
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
        return $this->advAppModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->advAppModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->advAppModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->advAppModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->advAppModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->advAppModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->advAppModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取应用
     * @param $page
     * @param $pageSize
     * @param $category
     * @return array|mixed
     */
    public function getAllOld($page=1,$pageSize=100,$category='装机必备',$isHot='')
    {
        $keyName = md5('adv_app_'.$page . '_' . $pageSize.'_'.$category);
        $items = getCache($keyName);
        if ($items===null) {
            $query['is_disabled'] = 0;
            if($category){
                $query['position'] = $category;
            }
            $items = $this->getList($query, array(), array('sort' => -1), ($page-1)*$pageSize, $pageSize);
            setCache($keyName, $items, mt_rand(100,200));
        }
        $result = array();
        foreach ($items as $item) {
            if($isHot!==''&&$isHot!=$item['is_hot']){continue;}
            $result[] = array(
                'id'          => strval($item['_id']),
                'name'        => strval($item['name']),
                'image'       => $this->commonService->getCdnUrl($item['image']),
                'ios_url'     => strval($item['download_url']),
                'android_url' => strval($item['download_url']),
                'download'    => strval($item['download']),
                'description' => strval($item['description']),
                'category'    => strval($item['position'])
            );
        }
        return empty($result)?array():$result;
    }

    /**
     * 获取应用
     * @param $page
     * @param $pageSize
     * @param $category
     * @param $isHot
     * @return array
     */
    public function getAll($page = 1, $pageSize = 100, $category='装机必备', $isHot='')
    {
        $config=container()->get('config');
        if($config->app->goNewAdCenter && $config->app->goNewAdCenter == 'y'){
            return $this->getAllOld($page, $pageSize, $category,$isHot);
        }

        $keyName = 'app_list_total';
        $cacheData = container()->get('redis')->get($keyName);
        $cacheData = !empty($cacheData)?json_decode($cacheData,true):[];

        $result = [];
        $items = [];
        $groupItems = $cacheData['items'][$category];
        foreach($groupItems as $groupItem){
            if($isHot!=''&&$groupItem['is_hot']!=$isHot){continue;}
            $items[] = $groupItem;
        }
        if(empty($items)){return $result;}

        foreach (array_chunk($items,$pageSize)[$page-1] as $value) {
            $result[] = [
                'id'          => strval($value['id']),
                'name'        => strval($value['name']),
                'image'       => $this->commonService->getCdnUrl($value['image']),
                'ios_url'     => strval($value['ios_url']),
                'android_url' => strval($value['android_url']),
                'download'    => strval($value['download']),
                'description' => strval($value['description']),
                'category'    => strval($value['category'])
            ];
        }
        return empty($result)?array():$result;
    }

}