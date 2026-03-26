<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\DomainModel;

/**
 *  域名管理
 * @package App\Services
 *
 * @property  DomainModel $domainModel
 */
class DomainService extends BaseService
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
        return $this->domainModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->domainModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->domainModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->domainModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $result= $this->domainModel->update($data, array("_id" => intval($data['_id'])));
        } else {
            $result= $this->domainModel->insert($data);
        }
        delCache('all_domain');
        return $result;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result= $this->domainModel->delete(array('_id' => intval($id)));
        delCache('all_domain');
        return $result;
    }

    /**
     * 获取所有可用的域名
     * @return mixed
     */
    public function getAll()
    {
       $result = getCache('all_domain');
        if(empty($result)){
            $result = $this->getList(array('status'=>0),array('url','type','channel_code'),array(),0,200);
            setCache('all_domain',$result,60*5);
        }
        return $result?:[];
    }

    /**
     * @param $keyField
     * @param $valueField
     * @return array
     */
    public function getAllGroupBy($keyField='type',$valueField='url',$dataType='array')
    {
        $result = [];
        $items = $this->getAll();
        foreach ($items as $item){
            if(empty($item[$keyField])||empty($item[$valueField])){continue;}
            $item['url'] = str_replace("https://","",$item['url']);
            $item['url'] = str_replace("http://","",$item['url']);
            $item['url'] = str_replace("/","",$item['url']);
            if($dataType=='array'){
                $result[$item[$keyField]][] =  $item[$valueField];
            }else{
                $result[$item[$keyField]] =  $item[$valueField];
            }

        }
        return $result;
    }

    public function getOne($url)
    {
        $cacheKey = "one_domain_".$url;
        $result = getCache($cacheKey);
        if($url&&empty($result)){
            $result = $this->findFirst(['url'=>strval($url)]);
            setCache($cacheKey,$result,60*5);
        }
        return $result;
    }

}