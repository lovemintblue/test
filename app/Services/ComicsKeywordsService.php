<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\ComicsKeywordsModel;
use App\Utils\CommonUtil;

/**
 *  关键字
 * @package App\Services
 *
 * @property  ComicsKeywordsModel $comicsKeywordsModel
 */
class ComicsKeywordsService extends BaseService
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
        return $this->comicsKeywordsModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->comicsKeywordsModel->count($query);
    }

    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->comicsKeywordsModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->comicsKeywordsModel->findByID($id);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->comicsKeywordsModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->comicsKeywordsModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->comicsKeywordsModel->delete(array('_id' => $id));
    }

    /**
     * 获取热门关键字
     * @param int $limit
     * @return array
     */
    public function getHotList($limit=10)
    {
        $cacheKey = 'comics_hot_keywords_'.'_'.$limit;
        $result = getCache($cacheKey);
        if(empty($result)){
            $query = ['is_hot'=>1];
            $rows=$this->getList($query,[],['sort'=>-1],0,intval($limit));
            $result = array();
            foreach ($rows as $index=>$row) {
                $result[]=[
                    'id'=>strval($row['_id']),
                    'name'=>strval($row['name']),
                    'click' => strval(CommonUtil::formatNum($row['num']*1)).'热度'
                ];
            }
            setCache($cacheKey,$result,mt_rand(200,300));
        }
        return empty($result)?array():$result;
    }


    /**
     * 写入关键字
     * @param $keywords
     * @return bool
     */
    public function do($keywords)
    {
        $keywords=trim($keywords);
        $id = md5($keywords);
        if ($this->findByID($id)) {
            $this->comicsKeywordsModel->updateRaw(['$inc'=>['num'=>1]],['_id'=>$id]);
        }else{
            $this->comicsKeywordsModel->insert([
                '_id'   =>$id,
                'name'  =>$keywords,
                'is_hot'=>0,
                'sort'  =>0,
            ]);
        }
        return true;
    }
}