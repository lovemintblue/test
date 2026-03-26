<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\UserBuyLogModel;

/**
 * 购买记录
 * Class UserBuyLogService
 * @property UserBuyLogModel $userBuyLogModel
 * @package App\Services
 */
class UserBuyLogService extends BaseService
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
        return $this->userBuyLogModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->userBuyLogModel->count($query);
    }

    /**
     * 是否购买
     * @param $userId
     * @param $objectId
     * @param $objectType
     * @return bool
     */
    public function has($userId,$objectId,$objectType)
    {
        $id     = md5($userId.'_'.$objectType.'_'.$objectId);
        $count  = $this->count(['_id'=>$id]);
        return $count>0?true:false;
    }

    /**
     * 增加记录
     * @param $orderSn
     * @param $userModel
     * @param $objectId
     * @param $objectType
     * @param $objectImg
     * @param $money
     * @param $moneyOld
     * @return bool|int
     * @throws BusinessException
     */
    public function do($orderSn,$userModel,$objectId,$objectType,$objectImg,$money,$moneyOld)
    {
        $id     = md5($userModel['_id'].'_'.$objectType.'_'.$objectId);
        $count  = $this->count(['_id'=>$id]);
        if($count>0){
            throw new BusinessException(StatusCode::DATA_ERROR, '您已经购买过了!');
        }
        $data=[
            '_id'       =>$id,
            'order_sn'  =>$orderSn,
            'user_id'   =>intval($userModel['_id']),
            'username'  =>$userModel['username'],
            'channel_name'=>$userModel['channel_name'],
            'object_id'   =>$objectId,
            'object_type' =>$objectType,
            'object_img'  =>$objectImg,
            'object_money'=>doubleval($money),
            'object_money_old'=>doubleval($moneyOld),
            'register_at' =>intval($userModel['register_at']),
            'label'       =>date("Y-m-d"),
        ];
        return $this->userBuyLogModel->insert($data);
    }

    /**
     * 购买记录
     * @param $userId
     * @param $type
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function log($userId,$type,$page=1,$pageSize=12)
    {
        $skip = ($page - 1) * $pageSize;
        $result = [];
        $items = $this->getList(['user_id'=>intval($userId),'object_type'=>$type],[],['created_at'=>-1],$skip,$pageSize);
        foreach ($items as $item) {
            $result[$item['object_id']] = array(
                'date_label'    => dateFormat($item['updated_at'],'Y-m-d'),
                'updated_time'  => strval($item['updated_at'])
            );
        }
        return $result;
    }
}