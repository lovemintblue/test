<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommonValues;
use App\Core\Services\BaseService;
use App\Models\UserActModel;

/**
 * 用户行为
 * @package App\Services
 *
 * @property UserActModel $userActModel
 * @property UserService $userService
 */
class UserActService extends BaseService
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
        return $this->userActModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query)
    {
        return $this->userActModel->count($query);
    }


    /**
     * 返回第一条数据
     * @param array $query
     * @param array $fields
     * @return array
     */
    public function findFirst($query = array(), $fields = array())
    {
        return $this->userActModel->findFirst($query, $fields);
    }

    /**
     * 通过id查询
     * @param  $id
     * @return mixed
     */
    public function findByID($id)
    {
        return $this->userActModel->findByID(intval($id));
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            return $this->userActModel->update($data, array("_id" => $data['_id']));
        } else {
            return $this->userActModel->insert($data);
        }
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->userActModel->delete(array('_id' => intval($id)));
    }

    /**
     * @param $userId
     * @param $action
     * @return true
     */
    public function addActQueue($userId, $act)
    {
        if(in_array($act,array_keys(CommonValues::getUserActs()))){
            $this->getRedis()->lPush('user_act',['act'=>$act,'user_id'=>$userId,'time'=>time()]);
        }
        return true;
    }

    /**
     * @return mixed
     */
    public function doActQueue()
    {
        $runTime = 297;//可执行时间/秒
        $startTime = time();
        while (true)
        {
            if (time() - $startTime >= $runTime) {
                break;
            }
            $data = $this->getRedis()->rPop('user_act');
            if(empty($data)){
                sleep(5);
                continue;
            }
            $userActKeys = array_keys(CommonValues::getUserActs());
            if(!in_array($data['act'],$userActKeys)){
                continue;
            }

            $act = [];
            $mustActNum = 0;
            $otherActNum = 0;
            $isValid = false;
            $_id = md5('user_act_'.$data['user_id']);
            $row =$this->userActModel->findFirst(['_id'=>$_id]);
            foreach($userActKeys as $userActKey){
                $act[$userActKey] = intval($row['act']->$userActKey);
                if($userActKey==$data['act']){
                    ++$act[$userActKey];
                }
                if(empty($act[$userActKey])){

                }elseif(in_array($userActKey,['enter_app','close_ad','close_appstore','close_notice'])){
                    ++$mustActNum;
                }else{
                    ++$otherActNum;
                }
            }

            //必点行为种类>=2种,其他行为种类>=2种
            if($mustActNum>=2&&$otherActNum>=2){
                $isValid = true;
            }

            $userInfo = $this->userService->getInfoFromCache($data['user_id']);
            if($userInfo['is_valid']!=='y'&&$isValid){
                $this->userService->updateRaw(['$set'=>[
                    'is_valid'=>1
                ]],['_id'=>intval($data['user_id'])]);
                $this->userService->setInfoToCache($data['user_id']);
            }

            if($row){
                $updateData = ['$inc'=>['act.'.$data['act']=>1]];
                if($isValid){
                    $updateData['$set'] = ['is_valid'=>1];
                }
                $this->userActModel->updateRaw($updateData,['_id'=>$_id]);
            }else{
                $this->userActModel->insert([
                    '_id'         => $_id,
                    'user_id'     => intval($data['user_id']),
                    'username'    => strval($userInfo['username']),
                    'channel_name'=> strval($userInfo['channel_name']),
                    'is_valid'    => 0,
                    'register_at' => intval($userInfo['register_at']),
                    'act'         => $act
                ]);
            }
        }
    }
}