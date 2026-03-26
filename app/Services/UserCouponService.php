<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\UserCouponModel;
use App\Models\UserCouponLogModel;
use App\Utils\CommonUtil;

/**
 * 优惠券
 * @package App\Services
 *
 * @property  UserCouponModel $userCouponModel
 * @property  UserCouponLogModel $userCouponLogModel
 * @property  UserService $userService
 */
class UserCouponService extends BaseService
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
        return $this->userCouponModel->find($query, $fields, $sort, $skip, $limit);
    }


    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->userCouponModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->userCouponModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->userCouponModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @param $isAdmin
     * @return bool|mixed
     */
    public function save($data,$isAdmin=false)
    {
        if ($data['_id']) {
            return $this->userCouponModel->update($data, array("_id" => $data['_id']));
        } else {
            $data['used_num'] = 0;
            $data['label'] = '';
            $data['code_key'] = substr(CommonUtil::getId(), 8, 16);
            $data['expired_at'] = $data['expired_at']?strtotime($data['expired_at']):strtotime('+10 years');
            $num = empty($data['num']) ? 1 : $data['num'] * 1;
            for ($index = 0; $index < $num; $index++) {
                while (true) {
                    $code = $this->createCode();
                    $codeRow = $this->userCouponModel->count(array('code' => $code));
                    if (empty($codeRow)) {
                        $data['code'] = $code;
                        $this->userCouponModel->insert($data);
                        break;
                    }
                }
            }
        }
        if($isAdmin){
            $keyName='user_card_'.$data['user_id'];
            delCache($keyName);
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
        return $this->userCouponModel->delete(array('_id' => intval($id)));
    }

    /**
     * 使用兑换码
     * @param $userId
     * @param $code
     * @return bool
     */
    public function doCode($userId,$code)
    {
        if(is_array($code)){

        }else{
            $code = $this->findFirst(['code'=>strval($code)]);
        }
        $query  = ['code'=>strval($code['code'])];
        $update = ['status'=>1,'used_num'=>1,'label'=>date('Y-m-d')];
        $res = $this->userCouponModel->update($update, $query);
        if($res){
            //写入兑换记录
            $_id=md5($userId.'_'.$code['code']);
            $this->userCouponLogModel->insert([
                '_id'=>$_id,
                'user_id'=>(int)$userId,
                'code_id'=>(int)$code['_id'],
                'code'=>$code['code']
            ]);
            $keyName='user_card_'.$userId;
            delCache($keyName);
        }
        return $res;
    }

    /**
     * 检查优惠券是否可以使用
     * @param $userId
     * @param $code
     * @return bool
     * @throws BusinessException
     */
    public function check($userId,$code)
    {
        if(is_array($code)){

        }else{
            $code = $this->findFirst(['code'=>strval($code)]);
        }
        if(empty($code)){
            throw new BusinessException(StatusCode::DATA_ERROR, '优惠券错误!');
        }
        if($userId!=0&&$userId!=$code['user_id']){
            throw new BusinessException(StatusCode::DATA_ERROR, '您没有该兑换码!');
        }

        if($code['status'] || $code['used_num']>=$code['can_use_num']){
            throw new BusinessException(StatusCode::DATA_ERROR, '该优惠券已使用!');
        }
        if($code['expired_at']<time()){
            throw new BusinessException(StatusCode::DATA_ERROR, '优惠券已经过期!');
        }
        return true;
    }

    /**
     * 获取用户可用优惠券数量
     * @param $userId
     * @param $type 观影movie  裸聊naked
     * @return int
     */
    public function getNums($userId,$type)
    {
        $query['user_id'] = intval($userId);
        $query['status']  = 0;
        if($type){$query['type']=strval($type);}
        return $this->count($query);
    }

    /**
     * 获取一张优惠券
     * @param $userId
     * @param $type
     * @return array
     */
    public function getOne($userId,$type)
    {
        $query['user_id'] = intval($userId);
        $query['status']  = 0;
        if($type){$query['type']=strval($type);}
        return $this->findFirst($query);
    }

    /**
     * 查询兑换码
     * @param $code
     * @return array
     */
    public function findByCode($code)
    {
        return $this->findFirst(['code'=>strval($code)]);
    }

    /**
     * 获取用户可用卡包
     * @param $userId
     * @return array
     */
    public function getCard($userId)
    {
        $keyName='user_card_'.$userId;
        $rows=getCache($keyName);
        if(is_null($rows)){
            $query = [
                'user_id' => intval($userId),
                'status'  => 0,
            ];
            $rows = $this->getList($query,['user_id','name','code','money'],['_id'=>-1],0,100);
            setCache($keyName, $rows,180);
        }
        $rows=CommonUtil::arrayGroup($rows,'money');
        return [
            [
                'money'=>"5",
                'name'=>'观影券',
                'desc'=>'观看金币视频时选择抵扣',
                'style'=>'1',
                'count'=>strval(isset($rows[5])?count($rows[5]):0),
            ],
            [
                'money'=>"10",
                'name'=>'观影券',
                'desc'=>'观看金币视频时选择抵扣',
                'style'=>'1',
                'count'=>strval(isset($rows[10])?count($rows[10]):0),
            ],
            [
                'money'=>"20",
                'name'=>'观影券',
                'desc'=>'观看金币视频时选择抵扣',
                'style'=>'1',
                'count'=>strval(isset($rows[20])?count($rows[20]):0),
            ],
            [
                'money'=>"30",
                'name'=>'观影券',
                'desc'=>'观看金币视频时选择抵扣',
                'style'=>'1',
                'count'=>strval(isset($rows[30])?count($rows[30]):0),
            ],
        ];
    }

    /**
     * 发放观影券
     * @param $userId
     * @param $num
     * @param $type
     * @param int $money
     * @return bool|mixed
     * @throws BusinessException
     */
    public function toUser($userId,$num,$type,$money=20)
    {
        if (!in_array($type,['movie','naked'])) {
            throw new BusinessException(StatusCode::DATA_ERROR, '暂不支持该类型!');
        }
        //生成指定张数代金券
        $res=$this->save([
            'name'  => $type=='movie'?"观影券{$money}元":"裸聊券{$money}元",
            'money' =>$money,
            'num'   =>$num,
            'type'  =>$type,
            'status'=>0,
            'can_use_num'=>1,
            'user_id'=>$userId
        ]);
        return $res;
    }

}