<?php


namespace App\Services;


use App\Constants\StatusCode;
use App\Core\Services\BaseService;
use App\Exception\BusinessException;
use App\Models\CommentLoveModel;
use App\Models\CommentModel;

/**
 * Class CommentLoveService
 * @property CommentModel $commentModel
 * @property CommentLoveModel $commentLoveModel
 * @package App\Services
 */
class CommentLoveService extends BaseService
{
    /**
     * 是否点赞
     * @param $userId
     * @param $commentId
     * @return bool
     */
    public function has($userId,$commentId)
    {
        $id=md5($userId.'_'.$commentId);
        $count = $this->commentLoveModel->count(array('_id' => $id));
        return $count>0?true:false;
    }

    /**
     * @param $userId
     * @param $commentId
     * @return bool
     * @throws BusinessException
     */
    public function do($userId,$commentId)
    {
        $userId = intval($userId);
        $id     = md5($userId.'_'.$commentId);
        $count  = $this->commentLoveModel->count(['_id'=>$id]);
        if ($count) {
            $this->commentLoveModel->delete(array('_id' => $id));
            $this->commentModel->updateRaw(['$inc'=>['love'=>-1]],['_id'=>$commentId]);
            return false;
        }
        $comment = $this->commentModel->findByID($commentId);
        if (empty($comment)) {
            throw new BusinessException(StatusCode::DATA_ERROR, '评论不存在!');
        }
        $this->commentLoveModel->insert([
            '_id'       =>$id,
            'user_id'   =>$userId,
            'comment_id'=>$commentId,
        ]);
        $this->commentModel->updateRaw(['$inc'=>['love'=>1]],['_id'=>$commentId]);
        return true;
    }
}