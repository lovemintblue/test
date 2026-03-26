<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\PlayFavoriteModel;
use App\Models\UserFollowModel;

/**
 * 玩法收藏
 * Class PlayFavoriteModel
 * @property PlayFavoriteModel $playFavoriteModel
 * @property PlayService $playService
 * @property CommonService $commonService
 * @property UserService $userService
 * @property QueueService $queueService
 * @package App\Services
 */
class PlayFavoriteService extends BaseService
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
        return $this->playFavoriteModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->playFavoriteModel->count($query);
    }


    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->playFavoriteModel->findByID($id, '_id', $fields);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $this->playFavoriteModel->update($data, array("_id" => $data['_id']));
            $cartoonId = $data['_id'];
        } else {
            $cartoonId = $this->playFavoriteModel->insert($data);
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
        return $this->playFavoriteModel->delete(['_id'=>$id]);
    }

    /**
     * 收藏
     * @param $userId
     * @param $playId
     * @return bool
     * @throws BusinessException
     */
    public function do($userId,$playId)
    {
        $favoriteId = md5($userId.'_'.$playId);
        if ($this->has($userId,$playId)) {
            $this->delete($favoriteId);
            $this->playService->handler(['action' => 'unFavorite','play_id'=>$playId]);
//            $this->queueService->join('movie', array('action' => 'unFavorite','movie_id'=>$movieId));
            return false;
        }
        $has = $this->playService->count(['_id'=>$playId]);
        if ($has==0) {throw new BusinessException(StatusCode::DATA_ERROR, '数据不存在!');}
        $this->playFavoriteModel->insert([
            '_id' => $favoriteId,
            'play_id' => intval($playId),
            'user_id' => intval($userId)
        ]);
        $this->playService->handler(['action' => 'favorite','play_id'=>$playId]);
//        $this->queueService->join('movie', ['action' => 'favorite','movie_id'=>$movieId]);
        return true;
    }

    /**
     * 是否收藏
     * @param $userId
     * @param $playId
     * @return bool
     */
    public function has($userId,$playId)
    {
        $favoriteId = md5($userId.'_'.$playId);
        $count = $this->count(array('_id' => $favoriteId));
        return $count>0?true:false;
    }

    /**
     * @param $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFavorites($userId,$page = 1, $pageSize = 15)
    {
        $skip   = ($page - 1) * $pageSize;
        $result = [];
        $items  = $this->playFavoriteModel->find(['user_id' => intval($userId)], [], ['_id' => -1], $skip, $pageSize);
        foreach ($items as $item) {
            $result[$item['play_id']] = array(
                'date_label'    => dateFormat($item['updated_at'],'Y-m-d'),
                'updated_time'  => strval($item['updated_at'])
            );
        }
        return $result;
    }

    /**
     * 删除一个
     * @param $userId
     * @param $cartoonId
     * @return bool
     */
    public function delFirst($userId, $playId)
    {
        $favoriteId = md5($userId.'_'.$playId);
        $this->delete($favoriteId);
        return true;
    }

    /**
     * 删除所有
     * @param $userId
     * @return mixed
     */
    public function deleteAll($userId)
    {
        return $this->playFavoriteModel->delete(['user_id'=>intval($userId)]);
    }

}