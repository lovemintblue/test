<?php

namespace App\Services;

use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\PostLoveModel;

/**
 * 帖子点赞
 * Class MovieFavoriteService
 * @property PostLoveModel $postLoveModel
 * @property CommonService $commonService
 * @property PostService $postService
 * @property QueueService $queueService
 * @package App\Services
 */
class PostLoveService extends BaseService
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
        return $this->postLoveModel->find($query, $fields, $sort, $skip, $limit);
    }

    /**
     * 获取总计
     * @param $query
     * @return integer
     */
    public function count($query=[])
    {
        return $this->postLoveModel->count($query);
    }


    /**
     * 通过id查询
     * @param  $id
     * @param  $fields
     * @return mixed
     */
    public function findByID($id, $fields = array())
    {
        return $this->postLoveModel->findByID($id, '_id', $fields);
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|int|mixed
     */
    public function save($data)
    {
        if ($data['_id']) {
            $this->postLoveModel->update($data, array("_id" => $data['_id']));
            $cartoonId = $data['_id'];
        } else {
            $cartoonId = $this->postLoveModel->insert($data);
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
        return $this->postLoveModel->delete(['_id'=>$id]);
    }

    /**
     * 点赞
     * @param $postId
     * @param $userId
     * @return bool
     * @throws BusinessException
     */
    public function do($postId,$userId)
    {
        $loveId = md5("{$postId}_{$userId}");
        if ($this->has($postId,$userId)) {
            $this->delete($loveId);
            $this->postService->handler(['action' => 'unLove','post_id'=>$postId]);
            return false;
        }
        $post = $this->postService->findByID($postId);
        if (empty($post)) {throw new BusinessException(StatusCode::DATA_ERROR, '帖子不存在!');}
        $this->postLoveModel->insert([
            '_id' => $loveId,
            'post_id' => $postId,
            'user_id' => intval($userId)
        ]);
        $this->postService->handler(['action' => 'love','post_id'=>$postId]);
        return true;
    }

    /**
     * 是否点赞
     * @param $postId
     * @param $userId
     * @return bool
     */
    public function has($postId,$userId)
    {
        $loveId = md5("{$postId}_{$userId}");
        $count = $this->count(array('_id' => $loveId));
        return $count>0?true:false;
    }
}