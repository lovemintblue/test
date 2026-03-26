<?php


namespace App\Services;


use App\Core\Services\BaseService;
use App\Models\AccountLogModel;

/**
 * 余额相关
 * Class AccountService
 * @property AccountLogModel $accountLogModel
 * @property UserService $userService
 * @package App\Services
 */
class AccountService extends BaseService
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
        return $this->accountLogModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->accountLogModel->count($query);
    }

    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->accountLogModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->accountLogModel->findByID(intval($id));
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->accountLogModel->delete(array('_id' => intval($id)));
    }

    /**
     * 增加用户金币余额
     * @param $user
     * @param $orderSn
     * @param $num
     * @param $type
     * @param string $remark
     * @param string $ext
     * @return bool
     */
    public function addBalance($user, $orderSn, $num, $type, $remark = '', $ext = '')
    {
        $num=intval($num);
        $data = array(
            'order_sn' => $orderSn,
            'user_id' => $user['_id'],
            'username' => $user['username'],
            'num' => $num,
            'num_log'=>doubleval($user['balance']+$num),//余额
            'type' => intval($type),//余额类型 getAccountLogsType
            'record_type' => 'point',
            'remark' => $remark,
            'ext' => $ext,
            'updated_at'=>time()
        );
        $result1 = $this->userService->findAndModify(array(
            '_id' => $user['_id']
        ), array(
            '$inc' => array('balance' => $num)
        ), array('_id'));
        if ($result1) {
            $result2 = $this->accountLogModel->insert($data);
            return empty($result2) ? false : true;
        }
        return false;
    }



    /**
     * 减少用户金币余额
     * @param $user
     * @param $orderSn
     * @param $num
     * @param $type
     * @param string $remark
     * @param string $ext
     * @return bool
     */
    public function reduceBalance($user, $orderSn, $num, $type, $remark = '', $ext = '')
    {
        $num = intval($num);
        $data = array(
            'order_sn' => $orderSn,
            'user_id' => $user['_id'],
            'username' => $user['username'],
            'num' => $num * -1,
            'num_log'=>doubleval($user['balance']-$num),//余额
            'type' => intval($type),//余额类型 getAccountLogsType
            'record_type' => 'point',
            'remark' => $remark,
            'ext' => $ext,
            'updated_at'=> time()
        );
        $result1 = $this->userService->findAndModify(array(
            '_id' => $user['_id'],
            'balance' => array('$gte' => $num)
        ), array(
            '$inc' => array('balance' => $num * -1)
        ), array('_id'));
        if ($result1) {
            $result2 = $this->accountLogModel->insert($data);
            $this->userService->setInfoToCache($user['_id']);
            return empty($result2) ? false : true;
        }
        return false;
    }
}