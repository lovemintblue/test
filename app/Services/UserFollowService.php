<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\UserFollowModel;
use App\Utils\LogUtil;

/**
 * 用户关注
 * Class UserFollowService
 * @property UserFollowModel $userFollowModel
 * @property CommonService $commonService
 * @property UserService $userService
 * @property UserActiveService $userActiveService
 * @property QueueService $queueService
 * @package App\Services
 */
class UserFollowService extends BaseService
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
        return $this->userFollowModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->userFollowModel->count($query);
    }


    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->userFollowModel->findByID($id, '_id', $fields);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $this->userFollowModel->update($data, array("_id" => $data['_id']));
            $cartoonId = $data['_id'];
        } else {
            $cartoonId = $this->userFollowModel->insert($data);
        }
        return $cartoonId;
    }

    /**
     * 删除数据
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->userFollowModel->delete(['_id'=>$id]);
    }

    /**
     * @param $userId
     * @param $homeId
     * @return bool
     */
    public function has($userId,$homeId)
    {
        $itemId = md5('user_follow_'.$homeId.'_'.$userId);
        $count  = $this->count(['_id'=>$itemId]);
        return $count?true:false;
    }


    /**
     * 关注
     * @param $userId
     * @param $homeId
     * @return string 1已经关注  0取消关注
     * @throws BusinessException
     */
    public function do($userId,$homeId)
    {
        $userId = intval($userId);
        $homeId = intval($homeId);
        $itemId = md5('user_follow_'.$homeId.'_'.$userId);
        $followModel = $this->findByID($itemId);
        if (!$this->commonService->checkActionLimit('user_follow_' . $userId, 60,10)) {
            $this->userService->doDisabled($userId,'请求异常!');
            throw new BusinessException(StatusCode::DATA_ERROR, '请求频繁请稍后再试!');
        }
        if($userId==$homeId){
            throw new BusinessException(StatusCode::DATA_ERROR, '自己不能关注自己!');
        }

        if(empty($followModel)){
            $data = array(
                '_id' => $itemId,
                'user_id' => intval($userId),
                'home_id' => intval($homeId)
            );
            $this->userFollowModel->insert($data);
            $this->userService->updateRaw(['$inc'=>['follow'=>1]],['_id'=>$userId]);
            $this->userService->updateRaw(['$inc'=>['fans'=>1]],['_id'=>$homeId]);
            $this->queueService->join('user_follow',[
                'user_id'  => $userId,
                'home_id'  =>$homeId
            ]);
            return '1';
        }else{
            $this->delete($itemId);
            $this->queueService->join('user_unfollow',[
                'user_id'  => $userId,
                'home_id'  =>$homeId
            ]);
            $this->userService->updateRaw(['$inc'=>['follow'=>-1]],['_id'=>$userId]);
            $this->userService->updateRaw(['$inc'=>['fans'=>-1]],['_id'=>$homeId]);
            return '0';
        }
    }

    /**
     * 关注的用户id
     * @param $userId
     * @param int $size
     * @return array
     */
    public function getFollowIds($userId,$size=20)
    {
        $rows=$this->getList(['user_id' =>intval($userId)],['home_id'],['created_at'=>-1],0,$size);
        return array_column($rows,'home_id');
    }

    /**
     * 关注列表
     * @param $userId
     * @param $homeId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFollowList($userId,$page=1,$pageSize=10){
        $userId= intval($userId);
        $rows = $this->getList(['user_id' =>$userId], ['home_id'], ["created_at" => -1], ($page - 1) * $pageSize, $pageSize);
        $result = array();
        if($rows){
            foreach ($rows as $key=>&$row) {
                $homeInfo = $this->userService->getInfoFromCache($row['home_id']);
                if (empty($homeInfo) || $homeInfo['is_disabled']) {
                    unset($rows[$key]);
                    continue;
                }
                $result[] = [
                    'user_id' => strval($homeInfo['id']),
                    'nickname' => $homeInfo['nickname'],
                    'img' => $this->commonService->getCdnUrl($homeInfo['img']),
                    'sex' => strval($homeInfo['sex']),
                    'is_up' => strval($homeInfo['is_up']),
                    'is_vip'=> $this->userService->isVip($homeInfo)?'y':'n',
                    'sing'=>strval($homeInfo['sing']),
                    'fans' => strval($homeInfo['fans']*1),
                    'follow'=> strval($homeInfo['follow']*1),

                ];
            }
        }
        return $result;
    }

    /**
     * 粉丝列表
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFansList($userId,$page=1,$pageSize=15){
        $userId= intval($userId);
        $rows = $this->getList(['home_id' =>$userId], ['user_id'], array("created_at" => -1), ($page - 1) * $pageSize, $pageSize);
        $result = array();
        if($rows){
            foreach ($rows as $key=>&$row) {
                $homeInfo=$this->userService->getInfoFromCache($row['user_id']);
                if(empty($homeInfo)||$homeInfo['is_disabled']){unset($rows[$key]);continue;}
                $result[]=[
                    'user_id' => strval($homeInfo['id']),
                    'nickname' => $homeInfo['nickname'],
                    'img' => $this->commonService->getCdnUrl($homeInfo['img']),
                    'sex' => strval($homeInfo['sex']),
                    'is_up' => strval($homeInfo['is_up']),
                    'is_vip'=> $this->userService->isVip($homeInfo)?'y':'n',
                    'sing'=>strval($homeInfo['sing']),
                    'fans' => strval($homeInfo['fans']*1),
                    'follow'=> strval($homeInfo['follow']*1),
                    'has_follow'=> $this->has($userId,$homeInfo['id'])?'y':'n'
                ];
            }
        }
        return $result;
    }

}