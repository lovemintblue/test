<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Services\BaseService;
use App\Models\AdvPosModel;

/**
 *  广告位置
 * @package App\Services
 *
 * @property  AdvPosModel $advPosModel
 */
class AdvPosService extends BaseService
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
        return $this->advPosModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->advPosModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->advPosModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->advPosModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->advPosModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->advPosModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->advPosModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取所有
     * @return array
     */
    public function getAll()
    {
        $items = $this->getList(array(),array(),array("sort"=>1),0,1000);
        $result = array();
        foreach ($items as $item)
        {
            $result[$item['code']] = array(
                'code' => $item['code'],
                'name' => $item['name'],
                'width' => $item['width'],
                'height' => $item['height'],
            );
        }
        return $result;
    }

}