<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\UserCodeLogModel;
use App\Models\UserCodeModel;
use App\Utils\CommonUtil;

/**
 *  兑换码
 * @package App\Services
 *
 * @property  UserCodeModel $userCodeModel
 * @property  UserService $userService
 * @property  AccountService $accountService
 * @property  ProductService $productService
 * @property  UserCodeLogModel $userCodeLogModel
 */
class UserCodeService extends BaseService
{
    /**
     * @return string
     * 生成兑换码
     */
    protected function createCode()
    {
        $string = 'QAZWSXEDCRFVTGBYHNUMJKLP123456789';
        $len = strlen($string);
        $returnString = '';
        for ($i = 1; $i <= 8; $i++) {
            $rand = mt_rand(0, $len - 1);
            $returnString .= $string[$rand];
        }
        return $returnString;
    }


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
        return $this->userCodeModel->find($query, $fields, $sort, $skip, $limit);
    }


    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->userCodeModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->userCodeModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->userCodeModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->userCodeModel->update($data, array("_id" => $data['_id']));
        } else {
            $data['used_num'] = 0;
            $data['code_key'] = substr(CommonUtil::getId(), 8, 16);
            if (empty($data['expired_at'])) {
                $data['expired_at'] = strtotime('+30 days');
            } else {
                $data['expired_at'] = strtotime($data['expired_at']);
            }
            $num = empty($data['num']) ? 1 : $data['num'] * 1;
            for ($index = 0; $index < $num; $index++) {
                while (true) {
                    $code = $this->createCode();
                    $codeRow = $this->userCodeModel->count(array('code' => $code));
                    if (empty($codeRow)) {
                        $data['code'] = $code;
                        $this->userCodeModel->insert($data);
                        break;
                    }
                }
            }
        }
        return true;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->userCodeModel->delete(array('_id' => intval($id)));
    }

    /**
     * 获取列表
     * @param array $query
     * @param array $fields
     * @param array $sort
     * @param int $skip
     * @param int $limit
     * @return array
     */
    public function getLogList($query = array(), $fields = array(), $sort = array(), $skip = 0, $limit = 10)
    {
        return $this->userCodeLogModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 使用兑换码
     * @param $userId
     * @param $code
     * @return bool
     * @throws BusinessException
     */
    public function doCode($userId,$code)
    {
        $code=strtoupper(strval($code));
        $userId = intval($userId);
        if (empty($code) || strlen($code) < 5) {
            throw new BusinessException(StatusCode::DATA_ERROR, '兑换码错误!');
        }
        $user = $this->userService->findByID($userId);
        $this->userService->checkUser($user);

        $userCode = $this->userCodeModel->findFirst(array('code' => $code));
        if(empty($userCode)){
            throw new BusinessException(StatusCode::DATA_ERROR, '兑换码不存在!');
        }
        if ($userCode['status'] || $userCode['used_num'] >= $userCode['can_use_num']) {
            throw new BusinessException(StatusCode::DATA_ERROR, '该兑换码已使用!');
        }
        $now = time();
        if ($userCode['expired_at'] < $now) {
            throw new BusinessException(StatusCode::DATA_ERROR, '兑换码已经过期!');
        }
        $userCodeLog = $this->userCodeLogModel->count(array('code_key' => $userCode['code_key'], 'user_id' => $userId));
        if ($userCodeLog) {
            throw new BusinessException(StatusCode::DATA_ERROR, '同一组兑换码只能用一次!');
        }
        $userCodeLog = $this->userCodeLogModel->find(array('user_id' => $userId), array(), array('created_at' => -1), 0, 1);
        if ($userCodeLog) {
            $codeEndTime = $userCodeLog['created_at'] + 86400 * $userCodeLog['add_num'];
            if ($now < $codeEndTime) {
                throw new BusinessException(StatusCode::DATA_ERROR, '兑换的vip未过期之前，不能重复兑换!');
            }
        }

        $this->userCodeLogModel->getConnection()->startTransaction();
        try {
            $usedNum = intval($userCode['used_num']) + 1;
            $update = array(
                '$inc' => array('used_num' => 1)
            );
            if ($usedNum == $userCode['can_use_num']) {
                $update['$set'] = array(
                    'status' => 1
                );
            }
            $result1 = $this->userCodeModel->updateRaw($update, array('_id' => $userCode['_id'], 'can_use_num' => array('$gte' => $usedNum)));
            $data = array(
                'name' => $userCode['name'],
                'type' => $userCode['type'],
                'code' => $userCode['code'],
                'code_id' => $userCode['_id'] * 1,
                'user_id' => $user['_id'],
                'object_id' => $userCode['object_id'] * 1,
                'username' => $user['username'],
                'code_key' => $userCode['code_key'],
                'add_num' => $userCode['add_num'] * 1
            );
            if (empty($result1)) {
                throw new BusinessException(StatusCode::DATA_ERROR, '兑换错误,请稍后再试!');
            }
            $result2 = $this->userCodeLogModel->insert($data);
            if($userCode['type']=='point'){//兑换金币
                $productInfo = $this->productService->getInfo($userCode['object_id']);
                if (empty($productInfo)) {
                    throw new BusinessException(StatusCode::DATA_ERROR, '金币套餐不存在!');
                }
                $result3 = $this->accountService->addBalance($user, CommonUtil::createOrderNo('AR'), $userCode['add_num'], 1, '使用金币兑换码',$userCode['code']);
            }else{
                $result3 = $this->userService->doChangeGroup($user, $userCode['add_num'], $userCode['object_id']);
            }
            if ($result2 && $result3) {
                $this->userCodeLogModel->getConnection()->commitTransaction();
                $this->userService->setInfoToCache($user['_id']);
                return true;
            }

        } catch (\Exception $exception) {

        }
        $this->userCodeLogModel->getConnection()->commitTransaction();
        throw new BusinessException(StatusCode::DATA_ERROR, '兑换错误,请稍后再试!');
    }

}