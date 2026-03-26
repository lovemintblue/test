<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKey;
use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\UserBalanceModel;
use App\Utils\CommonUtil;
use App\Utils\GameNameUtil;
use App\Utils\LogUtil;

/**
 * 用户余额
 * @package App\Services
 * @property CommonService $commonService
 * @property AiService $aiService
 * @property UserBalanceModel $userBalanceModel
 */
class UserBalanceService extends BaseService
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
        return $this->userBalanceModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->userBalanceModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->userBalanceModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->userBalanceModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $userInfo
     * @param $type
     * @return bool|mixed
     */
    public function save($userInfo,$type,$info='')
    {
        $userId = intval($userInfo['_id']);
        $row = $this->findByID($userId);
        if(empty($row)){
            $this->userBalanceModel->findAndModify(
                array('_id' => $userId), [
                '_id'=>$userId,
                'device_type'=>$userInfo['device_type'],
                'username'=>$userInfo['username'],
                'type'=>$type,
                'balance'=>$userInfo['balance'],
                'info'=>$info,
                'status'=>1,
                'error_num'=>0,
                'error_msg'=>'',
                'created_at'=>time(),
                'updated_at'=>time(),
            ], array('_id'), true);
            return true;
        }
        return $this->userBalanceModel->updateRaw(['$inc'=>['balance'=>$userInfo['balance']]],['_id'=>$userId]);
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $result = $this->userBalanceModel->delete(array('_id' => intval($id)));
        return $result;
    }

    /**
     * 游戏下分
     * @return mixed
     */
    public function balanceTransfer()
    {
        while (true){
            $rows = $this->getList([],[],['updated_at'=>1],0,100000);
            foreach($rows as $row){
                if($row['status']==3){
                    $this->aiService->getGirlFriendAuthUrl($row['_id']);
                    LogUtil::info("失败下分重新上分 type=>{$row['type']} ID=>{$row['_id']}");
                    sleep(10);
                }
                if(($row['status']==1&&$row['updated_at']<time()-12*60*60)||$row['status']==3){
                    $this->userBalanceModel->updateRaw(['$set'=>['status'=>2]],['_id'=>intval($row['_id'])]);
                    LogUtil::info("状态修改 type=>{$row['type']} ID=>{$row['_id']}");
                }elseif($row['status']==1){
                    continue;
                }
                if($this->aiService->girlFriendBringOutAssets($row['_id'],$row)){
                    LogUtil::info("下分成功 type=>{$row['type']} ID=>{$row['_id']}");
                }
            }
            sleep(5);
        }
    }

}