<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Core\Services\BaseService;
use App\Models\ComicsBlockModel;
use App\Models\MovieKeywordsModel;

/**
 *  漫画模块
 * @package App\Services
 *
 * @property  ComicsBlockModel $comicsBlockModel
 * @property BlockPositionService $blockPositionService
 */
class ComicsBlockService extends BaseService
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
        return $this->comicsBlockModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->comicsBlockModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->comicsBlockModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->comicsBlockModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $result = $this->comicsBlockModel->update($data, array("_id" => $data['_id']));
        } else {
            $result = $this->comicsBlockModel->insert($data);
        }
        return $result;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result = $this->comicsBlockModel->delete(array('_id' => intval($id)));
        return $result;
    }

    /**
     * 获取模块位置信息
     * @param $code
     * @return null
     */
    public function getBlockPostInfoByCode($code)
    {
        $items = $this->blockPositionService->getAll();
        return empty($items[$code])?null:$items[$code];
    }

    /**
     * 获取所有模块
     * @param int $page
     * @param int $pageSize
     * @param string $position
     * @return array|mixed
     */
    public function getListByCode($position,$page=1,$pageSize=8)
    {
        $keyName='comics_block_'.$page.'_'.$position;
        $result = getCache($keyName);
        if (is_null($result)) {
            $skip   = ($page - 1) * $pageSize;
            $result = array();
            $query['is_disabled'] = 0;
            if($position){$query['position'] = strval($position);}
            $items = $this->comicsBlockModel->find($query, array(), array('sort' => -1), $skip, $pageSize);
            foreach ($items as $item) {
                $result[] = array(
                    'id' => strval($item['_id']),
                    'name' => strval($item['name']),
                    'position' => strval($item['position']),
                    'filter' => strval($item['filter']),
                    'style' => strval($item['style']),
                    'num' => strval($item['num']),
                    'ico' => strval($item['ico'])
                );
            }
            setCache($keyName, $result, 300);
        }
        return empty($result)?array():$result;
    }

    /**
     * 搜索模块列表
     * @param $query
     * @return array
     */
    public function doSearch($query)
    {
        $filter = array();
        if($query['ids']){
            $ids = explode(',',$query['ids']);
            foreach ($ids as $index=>$id){
                $ids[$index] =intval($id);
            }
            $filter['_id'] = ['$in'=>array_values($ids)];
        }
        if($query['position']){
            $filter['position'] = $query['position'];
        }
        if($query['is_hot']){
            $filter['is_hot'] = 1;
        }
        $page = isset($query['page'])?intval($query['page']):1;
        $pageSize = isset($query['page_size'])?intval($query['page_size']):12;
        $items = $this->comicsBlockModel->find($filter,array(),array('sort'=>-1),($page-1)*$pageSize,$pageSize);
        $result = array();
        foreach ($items as $item) {
            $result[] = array(
                'id' => strval($item['_id']),
                'name' => strval($item['name']),
                'position' => strval($item['position']),
                'filter' => strval($item['filter']),
                'style' => strval($item['style']),
                'num' => strval($item['num']),
                'ico' => strval($item['ico'])
            );
        }
        return $result;
    }
}