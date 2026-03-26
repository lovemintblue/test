<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\UserActiveModel;
use App\Models\UserFollowModel;

/**
 * 用户活跃
 * Class UserActiveService
 * @property UserActiveModel $userActiveModel
 * @package App\Services
 */
class UserActiveService extends BaseService
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
        return $this->userActiveModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->userActiveModel->count($query);
    }


    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->userActiveModel->findByID($id, '_id', $fields);
    }

    /**
     * @param $userId
     * @return mixed
     */
    public function do($userId)
    {
        return true;
        $userId=intval($userId);
        return $this->userActiveModel->findAndModify(['_id'=>$userId],['updated_at'=>time()],[],true);
    }

    /**
     * 用户是否活跃
     * @param $userId
     * @param int $aging 时效/秒
     * @return bool
     */
    public function has($userId,$aging=1800)
    {
        return true;
        $userId=intval($userId);
        $row=$this->findByID($userId);
        if(empty($row)){
            return false;
        }
        return time()-$row['updated_at']<=$aging?true:false;
    }
}