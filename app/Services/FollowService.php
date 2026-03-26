<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\FollowModel;
use App\Models\UserFollowModel;
use App\Utils\LogUtil;

/**
 * 关注
 * Class UserFollowService
 * @property FollowModel $followModel
 * @property CommonService $commonService
 * @property UserService $userService
 * @property UserActiveService $userActiveService
 * @property QueueService $queueService
 * @package App\Services
 */
class FollowService extends BaseService
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
        return $this->followModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->followModel->count($query);
    }


    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->followModel->findByID($id, '_id', $fields);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $this->followModel->update($data, array("_id" => $data['_id']));
            $cartoonId = $data['_id'];
        } else {
            $cartoonId = $this->followModel->insert($data);
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
        return $this->followModel->delete(['_id'=>$id]);
    }

    public function getUid($userId,$objectId,$objectType)
    {
        return md5('follow_'.$objectId.'_'.$objectType.'_'.$userId);
    }

    /**
     * 获取是否已经关注
     * @param $userId
     * @param $objectId
     * @param $objectType
     * @return bool
     */
    public function has($userId,$objectId,$objectType)
    {
        $itemId = $this->getUid($userId,$objectId,$objectType);
        $count  = $this->count(['_id'=>$itemId]);
        return $count?true:false;
    }


    /**
     * 关注对象
     * @param $userId
     * @param $objectId
     * @param $objectType
     * @return string
     * @throws BusinessException
     */
    public function do($userId,$objectId,$objectType)
    {
        $userId = intval($userId);
        $objectId  = is_numeric($objectId)?intval($objectId):strval($objectId);
        $itemId = $this->getUid($userId,$objectId,$objectType);
        $followModel = $this->findByID($itemId);
        if (!$this->commonService->checkActionLimit('follow_' . $userId, 60,10)) {
            $this->userService->doDisabled($userId,'请求异常!');
            throw new BusinessException(StatusCode::DATA_ERROR, '请求频繁请稍后再试!');
        }

        if(empty($followModel)){
            $data = array(
                '_id'         => $itemId,
                'user_id'     => intval($userId),
                'object_id'   => $objectId,
                'object_type' => $objectType
            );
            $this->followModel->insert($data);
            return '1';
        }else{
            $this->delete($itemId);
            return '0';
        }
    }


    /**
     * 关注对象的id
     * @param $userId
     * @param $objectType
     * @param int $size
     * @return array
     */
    public function getFollowIds($userId,$objectType,$size=20)
    {
        $rows=$this->getList(['user_id' =>intval($userId),'object_type'=>$objectType],['object_id'],['created_at'=>-1],0,$size);
        return array_column($rows,'object_id');
    }


}