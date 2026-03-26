<?php

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\PostFavoriteModel;

/**
 * 帖子收藏
 * Class MovieFavoriteService
 * @property PostFavoriteModel $postFavoriteModel
 * @property CommonService $commonService
 * @property PostService $postService
 * @property QueueService $queueService
 * @package App\Services
 */
class PostFavoriteService extends BaseService
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
        return $this->postFavoriteModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->postFavoriteModel->count($query);
    }


    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->postFavoriteModel->findByID($id, '_id', $fields);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $this->postFavoriteModel->update($data, array("_id" => $data['_id']));
            $cartoonId = $data['_id'];
        } else {
            $cartoonId = $this->postFavoriteModel->insert($data);
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
        return $this->postFavoriteModel->delete(['_id'=>$id]);
    }

    /**
     * 收藏
     * @param $postId
     * @param $userId
     * @return bool
     * @throws BusinessException
     */
    public function do($postId,$userId)
    {
        $favoriteId = md5("{$postId}_{$userId}");
        if ($this->has($postId,$userId)) {
            $this->delete($favoriteId);
            $this->postService->handler(['action' => 'unFavorite','post_id'=>$postId]);
            return false;
        }else{
            $post = $this->postService->findByID($postId);
            if (empty($post)) {throw new BusinessException(StatusCode::DATA_ERROR, '帖子不存在!');}
            $this->postFavoriteModel->insert([
                '_id' => $favoriteId,
                'post_id' => $postId,
                'user_id' => intval($userId)
            ]);
            $this->postService->handler(['action' => 'favorite','post_id'=>$postId]);
            return true;
        }
    }

    /**
     * 是否收藏
     * @param $postId
     * @param $userId
     * @return bool
     */
    public function has($postId,$userId)
    {
        $favoriteId = md5("{$postId}_{$userId}");
        $count = $this->count(array('_id' => $favoriteId));
        return $count>0?true:false;
    }

    /**
     * 删除一个
     * @param $userId
     * @param $postId
     * @return bool
     */
    public function delFirst($userId, $postId)
    {
        $favoriteId = md5($postId.'_'.$userId);
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
        return $this->postFavoriteModel->delete(['user_id'=>intval($userId)]);
    }

}